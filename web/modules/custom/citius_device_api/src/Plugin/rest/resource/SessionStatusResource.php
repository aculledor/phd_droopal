<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\rest\resource;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\citius_content\NodeFields;
use Drupal\citius_content\SessionState;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Represents Session status records as resources.
 */
#[RestResource(
  id: 'citius_device_api_session_status',
  label: new TranslatableMarkup('Session status'),
  uri_paths: [
    'create' => '/api/session-status',
  ],
)]
class SessionStatusResource extends ResourceBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->httpClient = $container->get('http_client');
    return $instance;
  }

  /**
   * Responds to POST requests and saves the new record.
   *
   * @param array $data
   *   The data to save.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The modified resource response.
   */
  public function post(array $data): ModifiedResourceResponse {
    $id = $data['id'] ?? NULL;
    $action = $data['action'] ?? NULL;
    if (!$id || !$action) {
      throw new BadRequestHttpException('Invalid data format.');
    }
    $session = $this->entityTypeManager->getStorage('node')->load($id);
    if (!$session instanceof SessionNode) {
      throw new BadRequestHttpException('Invalid session id.');
    }
    $status = SessionState::from($data['status'] ?? '');
    $session->setSessionState($status);
    if ($status !== SessionState::Finished && in_array($action, ['reboot', 'stop'])) {
      $this->cleanResults($session);
    }
    if (in_array($action, ['reboot', 'start'])) {
      $session->set(NodeFields::DATE, (new DrupalDateTime())->format('Y-m-d\TH:i:s'));
    }
    $session->save();
    $field_definition = $this->entityTypeManager->getStorage('field_storage_config')
      ->load('node.' . NodeFields::SESSION_STATE);
    $allowed_values = $field_definition?->getSetting('allowed_values') ?? [];
    $status_label = $allowed_values[$status->value] ?? '';

    $bridge_endpoint = getenv('MQTT_BRIDGE_URL') ?: 'http://mqtt-drupal-bridge:3000/publish-command';
    $device_id = $session->getGlassDeviceId();

    $error_message = NULL;
    if ($device_id) {
      try {
        $glasses_response = $this->httpClient->post(
          $bridge_endpoint,
          [
            'json' => [
              'device_id' => $device_id,
              'user_id' => $session->get(NodeFields::PATIENT)->target_id,
              'routine_id' => $session->id(),
              'action' => $action,
            ],
            'timeout' => 5,
          ]
        );
      }
      catch (GuzzleException $e) {
        $this->logger->error($e->getMessage());
        $error_message = $this->t('Failed to send command to glasses device.');
      }
    }
    else {
      $error_message = $this->t('No glasses device is associated with this session.');
    }

    $response_data = [
      'status_label' => $status_label,
      'endpoint_status' => isset($glasses_response) ? 'success' : 'failure',
    ];
    if ($error_message) {
      $response_data['error_message'] = $error_message;
    }

    return new ModifiedResourceResponse($response_data, 201);
  }

  /**
   * Cleans session results.
   *
   * @param \Drupal\citius_content\Entity\SessionNode $session
   *   Session node.
   */
  protected function cleanResults(SessionNode $session): void {
    $results = $this->entityTypeManager
      ->getStorage('execution')
      ->loadByProperties([
        'session' => $session->id(),
      ]);
    foreach ($results as $result) {
      $result->delete();
    }
    $session->set(NodeFields::DATE, NULL);
  }

}
