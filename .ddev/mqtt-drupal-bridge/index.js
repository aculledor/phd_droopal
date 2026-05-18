import mqtt from "mqtt";
import axios from "axios";
import express from "express";

const MQTT_URL = process.env.MQTT_URL || "mqtt://mosquitto:1883";
const MQTT_USERNAME = process.env.MQTT_USERNAME || "vr_device";
const MQTT_PASSWORD = process.env.MQTT_PASSWORD || "";
const DRUPAL_BASE_URL = process.env.DRUPAL_BASE_URL || "http://web";
const HTTP_PORT = process.env.HTTP_PORT || 3000;
const ONLINE_TTL_MS = Number(process.env.ONLINE_TTL_MS || 45000);

const devices = new Map();

const mqttClient = mqtt.connect(MQTT_URL, {
  username: MQTT_USERNAME,
  password: MQTT_PASSWORD,
  reconnectPeriod: 3000,
});

mqttClient.on("connect", () => {
  console.log("Connected to MQTT broker");

  mqttClient.subscribe("vr/+/status");
  mqttClient.subscribe("vr/+/exercise");
  mqttClient.subscribe("vr/+/telemetry");
});

mqttClient.on("message", async (topic, payloadBuffer) => {
  const payloadText = payloadBuffer.toString();
  const deviceId = extractDeviceId(topic);

  let payload;

  try {
    payload = JSON.parse(payloadText);
  } catch {
    console.error("Invalid JSON:", topic, payloadText);
    return;
  }

  try {
    if (topic.endsWith("/status")) {
      registerHeartbeat(deviceId, payload);
      return;
    }

    if (topic.endsWith("/exercise")) {
      await sendExerciseToDrupal(payload);
      return;
    }

    if (topic.endsWith("/telemetry")) {
      console.log("Telemetry:", deviceId, payload);
      return;
    }
  } catch (error) {
    console.error("Error processing MQTT message:", {
      topic,
      error: error.response?.data || error.message,
    });
  }
});

function registerHeartbeat(deviceId, payload) {
  devices.set(deviceId, {
    device_id: deviceId,
    status: payload.status || "connected",
    token: payload.token || payload.access_token || null,
    last_seen_ms: Date.now(),
    last_seen: new Date().toISOString(),
  });

  console.log("Device heartbeat:", devices.get(deviceId));
}

async function sendExerciseToDrupal(payload) {
  const token = payload.token || payload.access_token;

  if (!token) {
    console.error("Missing Drupal bearer token in exercise payload");
    return;
  }

  const body = {
    metadata: payload.metadata,
    exercise_event: payload.exercise_event,
    movement_data: payload.movement_data,
  };

  const response = await axios.post(
    `${DRUPAL_BASE_URL}/api/exercise`,
    body,
    {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
      },
      timeout: 10000,
    }
  );

  console.log("Exercise sent to Drupal:", response.data);
}

function extractDeviceId(topic) {
  const parts = topic.split("/");
  return parts[1] || "unknown";
}

function isDeviceOnline(deviceId) {
  const device = devices.get(deviceId);

  if (!device) {
    return false;
  }

  return Date.now() - device.last_seen_ms <= ONLINE_TTL_MS;
}

const app = express();
app.use(express.json());

app.get("/devices/:device_id/status", (req, res) => {
  const { device_id } = req.params;
  const device = devices.get(device_id);

  if (!device) {
    return res.status(404).json({
      device_id,
      online: false,
      error: "Device has not sent heartbeat",
    });
  }

  return res.json({
    ...device,
    online: isDeviceOnline(device_id),
  });
});

app.post("/publish-command", (req, res) => {
  const { device_id, user_id, routine_id, action } = req.body;

  if (!device_id || !action) {
    return res.status(400).json({
      error: "device_id and action are required",
    });
  }

  if (!["start", "stop", "pause", "resume", "reboot"].includes(action)) {
    return res.status(400).json({
      error: "Invalid action",
      allowed_actions: ["start", "stop", "pause", "resume", "reboot"],
    });
  }

  if (!isDeviceOnline(device_id)) {
    return res.status(409).json({
      error: "Device is offline",
      device_id,
    });
  }

  const topic = `vr/${device_id}/command`;

  const message = {
    user_id,
    routine_id,
    action,
    timestamp: new Date().toISOString(),
  };

  mqttClient.publish(topic, JSON.stringify(message), { qos: 1 }, (error) => {
    if (error) {
      console.error("Failed to publish command:", error);
      return res.status(500).json({ error: "Failed to publish command" });
    }

    console.log("Command published:", topic, message);
    return res.json({ ok: true, topic, message });
  });
});

app.listen(HTTP_PORT, () => {
  console.log(`Bridge HTTP API listening on port ${HTTP_PORT}`);
});
