<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\rest\resource;

use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\NodeFields;
use Drupal\citius_device_api\DeviceIdManager;
use Drupal\citius_device_api\DeviceHeartbeatManager;
use Drupal\citius_device_api\DeviceTokenManager;
use Drupal\citius_device_api\Exception\DeviceIdException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Endpoint to register glasses in the system.
 */
#[RestResource(
  id: 'citius_device_api_authorize_glasses',
  label: new TranslatableMarkup('Authorize glasses'),
  uri_paths: [
    'create' => '/api/glass/authorize',
  ],
)]
class AuthorizeGlasses extends ResourceBase {

  /**
   * Device ID manager.
   *
   * @var \Drupal\citius_device_api\DeviceIdManager
   */
  protected DeviceIdManager $deviceIdManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Token manager.
   *
   * @var \Drupal\citius_device_api\DeviceTokenManager
   */
  protected DeviceTokenManager $deviceTokenManager;

  /**
   * Device heartbeat manager.
   *
   * @var \Drupal\citius_device_api\DeviceHeartbeatManager
   */
  protected DeviceHeartbeatManager $deviceHeartbeatManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->deviceIdManager = $container->get(DeviceIdManager::class);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->deviceTokenManager = $container->get(DeviceTokenManager::class);
    $instance->deviceHeartbeatManager = $container->get(DeviceHeartbeatManager::class);
    return $instance;
  }

  /**
   * Responds to POST requests.
   *
   * @param array $data
   *   The data to be validated.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   */
  public function post(array $data): ModifiedResourceResponse {
    $id = $data['id'] ?? NULL;
    $secret = $data['secret'] ?? NULL;
    if (!$id || !$secret) {
      throw new BadRequestHttpException('Invalid data format.');
    }
    $device = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => NodeBundles::DEVICE,
      NodeFields::CODE => $id,
      NodeFields::SECRET => $secret,
    ]);
    if (empty($device)) {
      try {
        $device_id = $this->deviceIdManager->assignDeviceId();
        $device_secret = $this->deviceIdManager->getDeviceSecret();
        if ($device_id !== $id || $device_secret !== $secret) {
          throw new AccessDeniedHttpException('Invalid device credentials.');
        }
      }
      catch (DeviceIdException $e) {
        throw new AccessDeniedHttpException('Invalid device credentials.', $e);
      }
      throw new AccessDeniedHttpException('Invalid device credentials.');
    }

    /** @var \Drupal\node\NodeInterface $device_node */
    $device_node = reset($device);
    if (!(bool) $device_node->get(NodeFields::STATE)->value) {
      $device_node->set(NodeFields::STATE, TRUE);
      $device_node->save();
    }
    $this->deviceHeartbeatManager->registerHeartbeat($id);

    return new ModifiedResourceResponse(['token' => $this->deviceTokenManager->getDeviceToken($id, $secret)], 201);
  }

}
