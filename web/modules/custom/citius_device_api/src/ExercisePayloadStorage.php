<?php

declare(strict_types=1);

namespace Drupal\citius_device_api;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;

/**
 * Stores raw and normalized exercise endpoint payloads.
 */
final class ExercisePayloadStorage {

  public const string TABLE_RAW = 'citius_device_api_exercise_raw_payload';
  public const string TABLE_TRACKING = 'citius_device_api_tracking_sample';

  public const string STATUS_RECEIVED = 'received';
  public const string STATUS_VALID = 'valid';
  public const string STATUS_PARTIALLY_VALID = 'partially_valid';
  public const string STATUS_INVALID = 'invalid';

  public function __construct(
    protected Connection $database,
  ) {}

  /**
   * Saves the raw request body as soon as it is available.
   */
  public function createRawPayload(string $raw_body, ?array $decoded_data, ?string $device_id = NULL): int {
    $payload_type = $decoded_data ? $this->detectPayloadType($decoded_data) : 'unknown';
    $validation_status = self::STATUS_RECEIVED;
    $validation_errors = [];

    if ($raw_body !== '' && $decoded_data === NULL) {
      $validation_status = self::STATUS_INVALID;
      $validation_errors[] = 'Request body is not valid JSON.';
    }

    return (int) $this->database->insert(self::TABLE_RAW)
      ->fields([
        'received_at' => $this->requestTimestamp(),
        'device_id' => $device_id,
        'user_id' => $decoded_data['metadata']['user_id'] ?? NULL,
        'routine_id' => $decoded_data['metadata']['routine_id'] ?? NULL,
        'payload_type' => $payload_type,
        'raw_payload_json' => $raw_body,
        'validation_status' => $validation_status,
        'validation_errors' => Json::encode($validation_errors),
      ])
      ->execute();
  }

  /**
   * Updates a raw payload log after endpoint validation/normalization.
   */
  public function updateRawPayload(int $raw_payload_id, array $fields): void {
    if ($raw_payload_id <= 0) {
      return;
    }

    if (isset($fields['validation_errors']) && is_array($fields['validation_errors'])) {
      $fields['validation_errors'] = Json::encode($fields['validation_errors']);
    }

    $this->database->update(self::TABLE_RAW)
      ->fields($fields)
      ->condition('id', $raw_payload_id)
      ->execute();
  }

  /**
   * Saves a normalized movement tracking sample.
   */
  public function createTrackingSample(array $data, ?int $raw_payload_id = NULL): int {
    $metadata = $data['metadata'] ?? [];
    $tracking_sample = $data['tracking_sample'] ?? [];

    return (int) $this->database->insert(self::TABLE_TRACKING)
      ->fields([
        'raw_payload_id' => $raw_payload_id ?: NULL,
        'received_at' => $this->requestTimestamp(),
        'sample_timestamp' => $tracking_sample['timestamp'] ?? $metadata['timestamp'] ?? NULL,
        'user_id' => $metadata['user_id'] ?? NULL,
        'routine_id' => $metadata['routine_id'] ?? NULL,
        'exercise_id' => $tracking_sample['exercise_id'] ?? NULL,
        'exercise_type_code' => $tracking_sample['exercise_type_code'] ?? NULL,
        'legacy_game_event' => $tracking_sample['legacy_game_event'] ?? NULL,
        'sample_interval_seconds' => isset($tracking_sample['sample_interval_seconds']) ? (float) $tracking_sample['sample_interval_seconds'] : NULL,
        'movement_data_json' => Json::encode($data['movement_data'] ?? []),
      ])
      ->execute();
  }

  /**
   * Detects the endpoint payload type from decoded JSON.
   */
  public function detectPayloadType(array $data): string {
    if (($data['metadata']['payload_type'] ?? NULL) === 'movement_tracking_sample' || isset($data['tracking_sample'])) {
      return 'movement_tracking_sample';
    }
    if (isset($data['exercise_event'])) {
      return 'exercise_event';
    }
    return 'unknown';
  }

  /**
   * Current server timestamp in an SQL-friendly UTC format.
   */
  protected function requestTimestamp(): string {
    return gmdate('Y-m-d H:i:s');
  }

}
