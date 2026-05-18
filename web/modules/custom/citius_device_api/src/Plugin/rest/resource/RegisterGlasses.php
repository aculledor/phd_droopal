<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\rest\resource;

use Drupal\citius_device_api\DeviceIdManager;
use Drupal\citius_device_api\Exception\DeviceIdException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Endpoint to register glasses in the system.
 */
#[RestResource(
  id: 'citius_device_api_register_glasses',
  label: new TranslatableMarkup('Register glasses'),
  uri_paths: [
    'create' => '/api/glass/register',
  ],
)]
class RegisterGlasses extends ResourceBase {

  /**
   * Device ID manager.
   *
   * @var \Drupal\citius_device_api\DeviceIdManager
   */
  protected DeviceIdManager $deviceIdManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->deviceIdManager = $container->get(DeviceIdManager::class);
    return $instance;
  }

  /**
   * Responds to POST requests.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   */
  public function post(): ModifiedResourceResponse {
    try {
      $device_id = $this->deviceIdManager->assignDeviceId();
      $device_secret = $this->deviceIdManager->getDeviceSecret();
    }
    catch (DeviceIdException $e) {
      throw new BadRequestHttpException($e->getMessage(), $e);
    }
    $data = [
      'id' => $device_id,
      'secret' => $device_secret,
    ];
    return new ModifiedResourceResponse($data, 201);
  }

}
