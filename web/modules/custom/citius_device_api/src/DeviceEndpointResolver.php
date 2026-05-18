<?php

declare(strict_types=1);

namespace Drupal\citius_device_api;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolve the device endpoint.
 */
final readonly class DeviceEndpointResolver {

  /**
   * Constructs a DeviceEndpointResolver object.
   */
  public function __construct(
    private RequestStack $requestStack,
  ) {}

  /**
   * Resolve the device endpoint.
   *
   * @return string|null
   *   The device endpoint.
   */
  public function resolve(): ?string {
    $request = $this->requestStack->getCurrentRequest();
    $ip = $request?->getClientIp();
    if (!$ip) {
      return NULL;
    }
    return "http://$ip:80/api/routine";
  }

}
