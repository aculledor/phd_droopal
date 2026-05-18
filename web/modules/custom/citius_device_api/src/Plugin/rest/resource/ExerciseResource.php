<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\rest\resource;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\citius_content\NodeFields;
use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Accepts POST request about exercise executions.
 */
#[RestResource(
  id: 'citius_device_api_exercise',
  label: new TranslatableMarkup('Exercise'),
  uri_paths: [
    'create' => '/api/exercise',
  ],
)]
class ExerciseResource extends ApiResourceBase {

  /**
   * Responds to POST requests and saves the new record.
   *
   * Expected payload format:
   * @code
   * {
   *   "metadata": {
   *     "version": "string",
   *     "timestamp": "string (ISO 8601 format)",
   *     "source": "string",
   *     "routine_id": "string",
   *     "user_id": "string"
   *   },
   *   "exercise_event": {
   *     "event_type": "string",
   *     "event_id": "string",
   *     "exercise_id": "string",
   *     "outcome": "string",
   *     "timestamp": "string (dd/MM/yyyy HH:mm)"
   *   },
   *   "movement_data": {
   *     "left_controller_x": "float",
   *     "left_controller_y": "float",
   *     "left_controller_z": "float",
   *     "right_controller_x": "float",
   *     "right_controller_y": "float",
   *     "right_controller_z": "float",
   *     "head_x": "float",
   *     "head_y": "float",
   *     "head_z": "float"
   *   }
   * }
   * @endcode
   *
   * @param array $data
   *   The data to be saved.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response containing the newly created record.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   */
  public function post(array $data): ModifiedResourceResponse {
    if (!$this->isAuthenticated()) {
      throw new AccessDeniedHttpException('Authentication required.');
    }
    if (!$this->validateDataFormat($data)) {
      throw new BadRequestHttpException('Invalid data format.');
    }
    $session = $this->loadSession($data);
    /** @var \Drupal\citius_device_api\Entity\ExecutionInterface $execution */
    $execution = $this->entityTypeManager->getStorage('execution')->create([
      'session' => $session,
      'exercise' => $data['exercise_event']['exercise_id'],
      'result' => $data['exercise_event']['outcome'],
      'execution_date' => $data['exercise_event']['timestamp'],
      'json_data' => Json::encode($data),
      'head_x' => $data['movement_data']['head_x'],
      'head_y' => $data['movement_data']['head_y'],
      'head_z' => $data['movement_data']['head_z'],
      'left_x' => $data['movement_data']['left_controller_x'],
      'left_y' => $data['movement_data']['left_controller_y'],
      'left_z' => $data['movement_data']['left_controller_z'],
      'right_x' => $data['movement_data']['right_controller_x'],
      'right_y' => $data['movement_data']['right_controller_y'],
      'right_z' => $data['movement_data']['right_controller_z'],
    ]);
    $violations_list = $execution->validate();
    if ($violations_list->count()) {
      throw new BadRequestHttpException('Invalid data format.');
    }
    $execution->save();
    return new ModifiedResourceResponse($data, 201);
  }

  /**
   * Simply validates data shape.
   *
   * @param array $data
   *   The data to be validated.
   *
   * @return bool
   *   True if the data is valid.
   */
  protected function validateDataFormat(array $data): bool {
    return isset(
      $data['metadata']['routine_id'],
      $data['metadata']['user_id'],
      $data['exercise_event']['exercise_id'],
      $data['exercise_event']['outcome'],
      $data['exercise_event']['timestamp'],
      $data['movement_data']['left_controller_x'],
      $data['movement_data']['left_controller_y'],
      $data['movement_data']['left_controller_z'],
      $data['movement_data']['right_controller_x'],
      $data['movement_data']['right_controller_y'],
      $data['movement_data']['right_controller_z'],
      $data['movement_data']['head_x'],
      $data['movement_data']['head_y'],
      $data['movement_data']['head_z'],
    );
  }

  /**
   * Loads and validates session.
   *
   * @param array $data
   *   Request payload.
   *
   * @return \Drupal\citius_content\Entity\SessionNode
   *   Session node.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   */
  protected function loadSession(array $data): SessionNode {
    $session_id = $data['metadata']['routine_id'];
    $session = $this->entityTypeManager->getStorage('node')->load($session_id);
    if (!$session instanceof SessionNode) {
      throw new BadRequestHttpException('Invalid session id.');
    }
    if ((int) $data['metadata']['user_id'] !== (int) $session->get(NodeFields::PATIENT)->target_id) {
      throw new BadRequestHttpException('Invalid user id.');
    }
    return $session;
  }

}
