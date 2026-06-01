<?php

declare(strict_types=1);

namespace Drupal\citius_device_api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

final class DeviceStatusChecker {

  public function __construct(
    protected ClientInterface $httpClient,
  ) {}

  public function getStatus(?string $device_id): array {
    if (!$device_id) {
      return [
        'online' => FALSE,
        'last_seen' => NULL,
        'status' => 'unknown',
      ];
    }

    $bridge_url = getenv('MQTT_BRIDGE_URL') ?: 'http://mqtt-drupal-bridge:3000';

    try {
      $response = $this->httpClient->get($bridge_url . '/devices/' . rawurlencode($device_id) . '/status', [
        'timeout' => 2,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);

      return [
        'online' => (bool) ($data['online'] ?? FALSE),
        'last_seen' => $data['last_seen'] ?? NULL,
        'status' => $data['status'] ?? 'unknown',
      ];
    }
    catch (GuzzleException|\Throwable) {
      return [
        'online' => FALSE,
        'last_seen' => NULL,
        'status' => 'unreachable',
      ];
    }
  }

}
