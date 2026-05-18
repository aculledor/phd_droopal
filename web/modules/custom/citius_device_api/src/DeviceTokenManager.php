<?php

declare(strict_types=1);

namespace Drupal\citius_device_api;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Device authentication token manager.
 */
final class DeviceTokenManager {

  private const string STORAGE_KEY = 'device_token';

  /**
   * Device ID manager lifetime in seconds.
   */
  private const int LIFETIME = 24 * 3600;

  private const string DELIMITER = ':';

  /**
   * Constructs a DeviceTokenManager object.
   */
  public function __construct(
    #[Autowire(service: 'keyvalue.expirable.database')]
    protected KeyValueDatabaseExpirableFactory $keyValueFactory,
    protected TimeInterface $time,
  ) {}

  /**
   * Get device token manager storage.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   Device token manager storage.
   */
  protected function storage(): KeyValueStoreExpirableInterface {
    return $this->keyValueFactory->get(self::STORAGE_KEY);
  }

  /**
   * Get device token.
   *
   * @param string $device_id
   *   Device ID.
   * @param string $device_secret
   *   Device secret.
   *
   * @return string
   *   Access token.
   */
  public function getDeviceToken(string $device_id, string $device_secret): string {
    $random = Crypt::hashBase64($device_id . $device_secret . $this->time->getRequestTime());
    $this->storage()->setWithExpire($device_id, $random, self::LIFETIME);
    return base64_encode($device_id . self::DELIMITER . $random);
  }

  /**
   * Validate token.
   *
   * @param string $token
   *   Token to validate.
   *
   * @return bool
   *   TRUE if the token is valid, FALSE otherwise.
   */
  public function validateToken(string $token): bool {
    $parts = explode(self::DELIMITER, base64_decode($token));
    if (count($parts) !== 2) {
      return FALSE;
    }
    [$device_id, $random] = $parts;
    $stored_random = $this->storage()->get($device_id);
    return $stored_random === $random;
  }

  /**
   * Get device ID from token.
   *
   * @param string $token
   *   Token to get device ID from.
   *
   * @return string|null
   *   Device ID from token.
   */
  public function getDeviceIdFromToken(string $token): ?string {
    $parts = explode(self::DELIMITER, base64_decode($token));
    return $parts[0] ?? NULL;
  }

}
