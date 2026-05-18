<?php

namespace Drupal\citius_device_api\Plugin\rest\resource;

use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\NodeFields;
use Drupal\citius_device_api\DeviceEndpointResolver;
use Drupal\citius_device_api\DeviceTokenManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for API resources.
 */
abstract class ApiResourceBase extends ResourceBase implements AuthenticatedResourceInterface {

  protected const string VERSION = 'v1';

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Device token manager.
   *
   * @var \Drupal\citius_device_api\DeviceTokenManager
   */
  protected DeviceTokenManager $deviceTokenManager;

  /**
   * Device endpoint resolver.
   *
   * @var \Drupal\citius_device_api\DeviceEndpointResolver
   */
  protected DeviceEndpointResolver $deviceEndpointResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->dateFormatter = $container->get('date.formatter');
    $instance->time = $container->get('datetime.time');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->requestStack = $container->get('request_stack');
    $instance->deviceTokenManager = $container->get(DeviceTokenManager::class);
    $instance->deviceEndpointResolver = $container->get(DeviceEndpointResolver::class);

    return $instance;
  }

  /**
   * Get request metadata.
   *
   * @return array
   *   Metadata for the API.
   */
  protected function getMetadata(): array {
    return [
      'version' => self::VERSION,
      'timestamp' => $this->dateFormatter->format($this->time->getRequestTime(), 'custom', 'c'),
      'source' => 'CiTIUS',
    ];
  }

  /**
   * Check if the request is authenticated.
   *
   * @return bool
   *   True if the request is authenticated.
   */
  public function isAuthenticated(): bool {
    $header = $this->requestStack->getCurrentRequest()?->headers->get('Authorization');
    if (!$header) {
      return FALSE;
    }
    $token = substr($header, 7);
    $is_valid = $this->deviceTokenManager->validateToken($token);
    $device_id = $this->deviceTokenManager->getDeviceIdFromToken($token);
    if (!$is_valid || !$device_id) {
      return FALSE;
    }
    $devices = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => NodeBundles::DEVICE,
      NodeFields::CODE => $device_id,
    ]);
    $device = reset($devices);
    if (!$device instanceof NodeInterface || $device->bundle() !== NodeBundles::DEVICE) {
      return FALSE;
    }
    /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem|null $item */
    $item = $device->get(NodeFields::ENDPOINT)->first();
    $endpoint = $item?->uri;
    $new_endpoint = $this->deviceEndpointResolver->resolve();
    if ($new_endpoint !== $endpoint) {
      $device->set(NodeFields::ENDPOINT, $new_endpoint)->save();
    }
    return TRUE;
  }

}
