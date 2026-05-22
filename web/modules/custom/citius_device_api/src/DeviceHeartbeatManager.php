<?php

declare(strict_types=1);

namespace Drupal\citius_device_api;

use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\NodeFields;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Manages heartbeat status for devices.
 */
final class DeviceHeartbeatManager {

  private const string STORAGE_KEY = 'device_heartbeat';

  private const int HEARTBEAT_TTL = 60;

  public function __construct(
    #[Autowire(service: 'keyvalue.expirable.database')]
    protected KeyValueDatabaseExpirableFactory $keyValueFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  protected function storage(): KeyValueStoreExpirableInterface {
    return $this->keyValueFactory->get(self::STORAGE_KEY);
  }

  public function registerHeartbeat(string $device_id): void {
    $this->storage()->setWithExpire($device_id, TRUE, self::HEARTBEAT_TTL);
  }

  public function hasRecentHeartbeat(string $device_id): bool {
    return (bool) $this->storage()->get($device_id);
  }

  public function markStaleDevicesInactive(): void {
    $devices = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => NodeBundles::DEVICE,
      NodeFields::STATE => 1,
    ]);

    foreach ($devices as $device) {
      $device_id = (string) $device->get(NodeFields::CODE)->value;
      if ($device_id && !$this->hasRecentHeartbeat($device_id)) {
        $device->set(NodeFields::STATE, FALSE);
        $device->save();
      }
    }
  }

}
