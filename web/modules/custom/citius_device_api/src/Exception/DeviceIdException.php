<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Exception;

/**
 * Exception thrown when device ID management operations fail.
 */
class DeviceIdException extends \RuntimeException {

  public const int MANAGER_NOT_ACTIVE = 1;

  public const int MANAGER_ALREADY_ACTIVE = 2;

  public const int ID_NOT_CLAIMED = 3;

  public const int ID_ALREADY_CLAIMED = 4;

}
