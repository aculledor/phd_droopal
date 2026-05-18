<?php

declare(strict_types=1);

namespace Drupal\citius_device_api;

use Drupal\citius_device_api\Exception\DeviceIdException;
use Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Handles device Id operations.
 */
final class DeviceIdManager {

  /**
   * Device ID manager lifetime in seconds.
   */
  public const int DEVICE_MANAGER_LIFETIME = 600;

  public const string STORAGE_KEY = 'device_id_manager';

  public const string STATUS_KEY = 'status';

  public const string DEVICE_ID_KEY = 'device_id';

  public const string DEVICE_SECRET_KEY = 'device_secret';

  public const string DEVICE_ID_IS_CLAIMED = 'claimed';

  public const string DEVICE_ENDPOINT_KEY = 'device_endpoint';

  public function __construct(
    #[Autowire(service: 'keyvalue.expirable.database')]
    protected KeyValueDatabaseExpirableFactory $keyValueFactory,
    protected DeviceEndpointResolver $deviceEndpointResolver,
  ) {}

  /**
   * Get device ID manager storage.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   Device ID manager storage.
   */
  protected function storage(): KeyValueStoreExpirableInterface {
    return $this->keyValueFactory->get(self::STORAGE_KEY);
  }

  /**
   * Save value with expiration.
   *
   * @param string $key
   *   Key to save.
   * @param mixed $value
   *   Value to save.
   */
  protected function saveValue(string $key, mixed $value): void {
    $this->storage()->setWithExpire($key, $value, self::DEVICE_MANAGER_LIFETIME);
  }

  /**
   * Activate service.
   */
  public function activate(): void {
    if ($this->isActivated()) {
      throw new DeviceIdException('Device ID manager is already activated.', DeviceIdException::MANAGER_ALREADY_ACTIVE);
    }
    $this->saveValue(self::STATUS_KEY, TRUE);
    $this->saveValue(self::DEVICE_ID_KEY, bin2hex(random_bytes(16)));
    $this->saveValue(self::DEVICE_SECRET_KEY, bin2hex(random_bytes(32)));
  }

  /**
   * Deactivate service.
   */
  public function deactivate(): void {
    if (!$this->isActivated()) {
      return;
    }
    $this->storage()->deleteAll();
  }

  /**
   * Check if service is activated.
   *
   * @return bool
   *   TRUE if service is activated, FALSE otherwise.
   */
  public function isActivated(): bool {
    return $this->storage()->get(self::STATUS_KEY) ?? FALSE;
  }

  /**
   * Assign device ID to device.
   *
   * @return string
   *   Device ID.
   */
  public function assignDeviceId(): string {
    if (!$this->isActivated()) {
      throw new DeviceIdException('Device ID manager is not activated.', DeviceIdException::MANAGER_NOT_ACTIVE);
    }
    if ($this->storage()->get(self::DEVICE_ID_IS_CLAIMED)) {
      throw new DeviceIdException('Device ID is already claimed.', DeviceIdException::ID_ALREADY_CLAIMED);
    }
    $this->saveValue(self::DEVICE_ID_IS_CLAIMED, TRUE);
    $this->saveValue(self::DEVICE_ENDPOINT_KEY, $this->deviceEndpointResolver->resolve());
    return $this->storage()->get(self::DEVICE_ID_KEY);
  }

  /**
   * Validate manager state.
   */
  protected function validateManagerState(): void {
    if (!$this->isActivated()) {
      throw new DeviceIdException('Device ID manager is not activated.', DeviceIdException::MANAGER_NOT_ACTIVE);
    }
    if (!$this->storage()->get(self::DEVICE_ID_IS_CLAIMED)) {
      throw new DeviceIdException('Device ID is not claimed yet.', DeviceIdException::ID_NOT_CLAIMED);
    }
  }

  /**
   * Get device ID.
   *
   * @return string
   *   Device ID.
   */
  public function getDeviceId(): string {
    $this->validateManagerState();
    return $this->storage()->get(self::DEVICE_ID_KEY);
  }

  /**
   * Get device secret.
   *
   * @return string
   *   Device secret.
   */
  public function getDeviceSecret(): string {
    $this->validateManagerState();
    return $this->storage()->get(self::DEVICE_SECRET_KEY);
  }

  /**
   * Get device endpoint.
   *
   * @return string|null
   *   Device endpoint.
   */
  public function getDeviceEndpoint(): ?string {
    $this->validateManagerState();
    return $this->storage()->get(self::DEVICE_ENDPOINT_KEY);
  }

}
