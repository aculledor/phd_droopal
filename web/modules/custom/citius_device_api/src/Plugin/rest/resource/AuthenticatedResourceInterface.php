<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\rest\resource;

/**
 * Interface for authenticated REST resources.
 */
interface AuthenticatedResourceInterface {

  /**
   * Check if the request is authenticated.
   *
   * @return bool
   *   True if the request is authenticated.
   */
  public function isAuthenticated(): bool;

}
