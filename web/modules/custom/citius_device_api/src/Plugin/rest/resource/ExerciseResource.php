<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\rest\resource;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\citius_content\NodeFields;
use Drupal\citius_device_api\ExecutionResult;
use Drupal\citius_device_api\ExercisePayloadStorage;
use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Accepts POST request about exercise executions and movement tracking samples.
 */
#[RestResource(
  id: 'citius_device_api_exercise',
  label: new TranslatableMarkup('Exercise'),
  uri_paths: [
    'create' => '/api/exercise',
  ],
)]
class ExerciseResource extends ApiResourceBase {

  protected ExercisePayloadStorage $exercisePayloadStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->exercisePayloadStorage = $container->get(ExercisePayloadStorage::class);
    return $instance;
  }

  /**
   * Responds to POST requests and saves raw/normalized exercise data.
   *
   * @param array $data
   *   The decoded JSON payload.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   A Unity-compatible JSON response describing what was saved.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function post(array $data): ModifiedResourceResponse {
    if (!$this->isAuthenticated()) {
      $this->markRawPayloadInvalid(['Authentication failed.']);
      throw new AccessDeniedHttpException('Authentication required.');
    }

    $raw_payload_id = $this->getRawPayloadId($data);
    $payload_type = $this->exercisePayloadStorage->detectPayloadType($data);

    return match ($payload_type) {
      'movement_tracking_sample' => $this->processTrackingSample($data, $raw_payload_id),
      'exercise_event' => $this->processExerciseEvent($data, $raw_payload_id),
      default => $this->processUnknownPayload($data, $raw_payload_id),
    };
  }

  /**
   * Processes a normal exercise result payload without changing statistics semantics.
   */
  protected function processExerciseEvent(array $data, int $raw_payload_id): ModifiedResourceResponse {
    $errors = $this->validateExerciseEventPayload($data);
    $session = NULL;
    if (!$errors) {
      try {
        $session = $this->loadSession($data);
      }
      catch (\Throwable $exception) {
        $errors[] = $exception->getMessage();
      }
    }

    if ($errors) {
      $this->updateRawPayload($raw_payload_id, $data, 'exercise_event', ExercisePayloadStorage::STATUS_INVALID, $errors);
      return new ModifiedResourceResponse([
        'status' => 'accepted',
        'payload_type' => 'exercise_event',
        'raw_payload_saved' => TRUE,
        'normalized_payload_saved' => FALSE,
        'validation_status' => ExercisePayloadStorage::STATUS_INVALID,
        'validation_errors' => $errors,
      ], 202);
    }

    /** @var \Drupal\citius_device_api\Entity\ExecutionInterface $execution */
    $execution = $this->entityTypeManager->getStorage('execution')->create($this->buildExecutionValues($data, $session));
    $violations_list = $execution->validate();
    if ($violations_list->count()) {
      $errors = [];
      foreach ($violations_list as $violation) {
        $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
      }
      $this->updateRawPayload($raw_payload_id, $data, 'exercise_event', ExercisePayloadStorage::STATUS_INVALID, $errors);
      \Drupal::logger('citius_device_api')->error('Execution validation failed: @errors. Payload: @payload', [
        '@errors' => implode(' | ', $errors),
        '@payload' => Json::encode($data),
      ]);

      return new ModifiedResourceResponse([
        'status' => 'accepted',
        'payload_type' => 'exercise_event',
        'raw_payload_saved' => TRUE,
        'normalized_payload_saved' => FALSE,
        'validation_status' => ExercisePayloadStorage::STATUS_INVALID,
        'validation_errors' => $errors,
      ], 202);
    }

    $execution->save();
    $this->updateRawPayload($raw_payload_id, $data, 'exercise_event', ExercisePayloadStorage::STATUS_VALID, []);

    return new ModifiedResourceResponse([
      'status' => 'ok',
      'payload_type' => 'exercise_event',
      'raw_payload_saved' => TRUE,
      'normalized_payload_saved' => TRUE,
      'execution_id' => $execution->id(),
    ], 201);
  }

  /**
   * Processes a movement_tracking_sample payload outside execution statistics.
   */
  protected function processTrackingSample(array $data, int $raw_payload_id): ModifiedResourceResponse {
    $errors = $this->validateTrackingSamplePayload($data);
    $status = $errors ? ExercisePayloadStorage::STATUS_PARTIALLY_VALID : ExercisePayloadStorage::STATUS_VALID;

    $tracking_sample_id = NULL;
    try {
      $tracking_sample_id = $this->exercisePayloadStorage->createTrackingSample($data, $raw_payload_id);
    }
    catch (\Throwable $exception) {
      $errors[] = $exception->getMessage();
      $status = ExercisePayloadStorage::STATUS_INVALID;
    }

    $this->updateRawPayload($raw_payload_id, $data, 'movement_tracking_sample', $status, $errors);

    return new ModifiedResourceResponse([
      'status' => $status === ExercisePayloadStorage::STATUS_INVALID ? 'accepted' : 'ok',
      'payload_type' => 'movement_tracking_sample',
      'raw_payload_saved' => TRUE,
      'tracking_sample_saved' => $tracking_sample_id !== NULL,
      'tracking_sample_id' => $tracking_sample_id,
      'validation_status' => $status,
      'validation_errors' => $errors,
    ], $status === ExercisePayloadStorage::STATUS_INVALID ? 202 : 201);
  }

  /**
   * Stores an unknown JSON payload only in the raw payload log.
   */
  protected function processUnknownPayload(array $data, int $raw_payload_id): ModifiedResourceResponse {
    $errors = ['Payload did not match a known schema.'];
    $this->updateRawPayload($raw_payload_id, $data, 'unknown', ExercisePayloadStorage::STATUS_PARTIALLY_VALID, $errors);

    return new ModifiedResourceResponse([
      'status' => 'accepted',
      'payload_type' => 'unknown',
      'raw_payload_saved' => TRUE,
      'normalized_payload_saved' => FALSE,
      'message' => 'Payload saved as raw data but did not match a known schema.',
      'validation_status' => ExercisePayloadStorage::STATUS_PARTIALLY_VALID,
      'validation_errors' => $errors,
    ], 202);
  }

  /**
   * Builds execution entity values, including optional quaternion rotations.
   */
  protected function buildExecutionValues(array $data, SessionNode $session): array {
    $movement_data = $data['movement_data'];
    $values = [
      'session' => $session,
      'exercise' => $data['exercise_event']['exercise_id'],
      'result' => $data['exercise_event']['outcome'],
      'execution_date' => $data['exercise_event']['timestamp'],
      'json_data' => Json::encode($data),
      'head_x' => $movement_data['head_x'],
      'head_y' => $movement_data['head_y'],
      'head_z' => $movement_data['head_z'],
      'left_x' => $movement_data['left_controller_x'],
      'left_y' => $movement_data['left_controller_y'],
      'left_z' => $movement_data['left_controller_z'],
      'right_x' => $movement_data['right_controller_x'],
      'right_y' => $movement_data['right_controller_y'],
      'right_z' => $movement_data['right_controller_z'],
    ];

    foreach ($this->rotationFieldMap() as $payload_field => $entity_field) {
      if (array_key_exists($payload_field, $movement_data) && $movement_data[$payload_field] !== '') {
        $values[$entity_field] = (float) $movement_data[$payload_field];
      }
    }

    return $values;
  }

  /**
   * Validates normal exercise result payloads.
   */
  protected function validateExerciseEventPayload(array $data): array {
    $errors = $this->validateRequiredFields($data, [
      'metadata.routine_id',
      'metadata.user_id',
      'exercise_event.exercise_id',
      'exercise_event.outcome',
      'exercise_event.timestamp',
      'movement_data.left_controller_x',
      'movement_data.left_controller_y',
      'movement_data.left_controller_z',
      'movement_data.right_controller_x',
      'movement_data.right_controller_y',
      'movement_data.right_controller_z',
      'movement_data.head_x',
      'movement_data.head_y',
      'movement_data.head_z',
    ]);

    $outcome = $data['exercise_event']['outcome'] ?? NULL;
    $allowed_outcomes = array_map(static fn(ExecutionResult $result): string => $result->value, ExecutionResult::cases());
    if ($outcome !== NULL && !in_array($outcome, $allowed_outcomes, TRUE)) {
      $errors[] = sprintf('Invalid exercise_event.outcome "%s". Allowed values: %s.', $outcome, implode(', ', $allowed_outcomes));
    }

    return $errors;
  }

  /**
   * Validates movement tracking sample payloads without requiring outcome.
   */
  protected function validateTrackingSamplePayload(array $data): array {
    return $this->validateRequiredFields($data, [
      'metadata.routine_id',
      'metadata.user_id',
      'tracking_sample.timestamp',
      'tracking_sample.exercise_id',
      'movement_data',
    ]);
  }

  /**
   * Validates dotted required field paths.
   */
  protected function validateRequiredFields(array $data, array $required_paths): array {
    $errors = [];
    foreach ($required_paths as $path) {
      $value = $this->getNestedValue($data, explode('.', $path));
      if ($value === NULL || $value === '') {
        $errors[] = sprintf('Missing required field: %s.', $path);
      }
    }
    return $errors;
  }

  /**
   * Gets a nested value from an array by path parts.
   */
  protected function getNestedValue(array $data, array $path): mixed {
    $current = $data;
    foreach ($path as $part) {
      if (!is_array($current) || !array_key_exists($part, $current)) {
        return NULL;
      }
      $current = $current[$part];
    }
    return $current;
  }

  /**
   * Maps Unity rotation payload fields to execution entity fields.
   */
  protected function rotationFieldMap(): array {
    return [
      'left_controller_rot_x' => 'left_rot_x',
      'left_controller_rot_y' => 'left_rot_y',
      'left_controller_rot_z' => 'left_rot_z',
      'left_controller_rot_w' => 'left_rot_w',
      'right_controller_rot_x' => 'right_rot_x',
      'right_controller_rot_y' => 'right_rot_y',
      'right_controller_rot_z' => 'right_rot_z',
      'right_controller_rot_w' => 'right_rot_w',
      'head_rot_x' => 'head_rot_x',
      'head_rot_y' => 'head_rot_y',
      'head_rot_z' => 'head_rot_z',
      'head_rot_w' => 'head_rot_w',
    ];
  }

  /**
   * Loads and validates session.
   *
   * @param array $data
   *   Request payload.
   *
   * @return \Drupal\citius_content\Entity\SessionNode
   *   Session node.
   */
  protected function loadSession(array $data): SessionNode {
    $session_id = $data['metadata']['routine_id'];
    $session = $this->entityTypeManager->getStorage('node')->load($session_id);
    if (!$session instanceof SessionNode) {
      throw new \InvalidArgumentException('Invalid session id.');
    }
    if ((int) $data['metadata']['user_id'] !== (int) $session->get(NodeFields::PATIENT)->target_id) {
      throw new \InvalidArgumentException('Invalid user id.');
    }
    return $session;
  }

  /**
   * Updates the raw payload row with validation/identification metadata.
   */
  protected function updateRawPayload(int $raw_payload_id, array $data, string $payload_type, string $validation_status, array $validation_errors): void {
    $this->exercisePayloadStorage->updateRawPayload($raw_payload_id, [
      'user_id' => $data['metadata']['user_id'] ?? NULL,
      'routine_id' => $data['metadata']['routine_id'] ?? NULL,
      'payload_type' => $payload_type,
      'validation_status' => $validation_status,
      'validation_errors' => $validation_errors,
    ]);
  }

  /**
   * Marks the raw payload invalid for hard failures, when a raw row exists.
   */
  protected function markRawPayloadInvalid(array $errors): void {
    $request = $this->requestStack->getCurrentRequest();
    $raw_payload_id = (int) ($request?->attributes->get('citius_device_api_raw_payload_id') ?? 0);
    $this->exercisePayloadStorage->updateRawPayload($raw_payload_id, [
      'validation_status' => ExercisePayloadStorage::STATUS_INVALID,
      'validation_errors' => $errors,
    ]);
  }

  /**
   * Gets the pre-created raw payload id, falling back to creating one if needed.
   */
  protected function getRawPayloadId(array $data): int {
    $request = $this->requestStack->getCurrentRequest();
    $raw_payload_id = (int) ($request?->attributes->get('citius_device_api_raw_payload_id') ?? 0);
    if ($raw_payload_id > 0) {
      return $raw_payload_id;
    }

    return $this->exercisePayloadStorage->createRawPayload(
      $request?->getContent() ?: Json::encode($data),
      $data,
      $this->getDeviceIdFromAuthorizationHeader($request?->headers->get('Authorization')),
    );
  }

  /**
   * Gets the device id encoded in a bearer token without requiring validity.
   */
  protected function getDeviceIdFromAuthorizationHeader(?string $header): ?string {
    if (!$header || !str_starts_with($header, 'Bearer ')) {
      return NULL;
    }
    return $this->deviceTokenManager->getDeviceIdFromToken(substr($header, 7));
  }

}
