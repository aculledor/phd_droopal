<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Controller;

use Drupal\citius_device_api\DeviceStatusChecker;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class DeviceStatusController extends ControllerBase {

  public function __construct(
    protected DeviceStatusChecker $deviceStatusChecker,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('citius_device_api.device_status_checker'),
    );
  }

  public function status(string $device_id): JsonResponse {
    $status = $this->deviceStatusChecker->getStatus($device_id);

    return new JsonResponse([
      'device_id' => $device_id,
      'online' => (bool) ($status['online'] ?? FALSE),
      'status' => $status['status'] ?? 'unknown',
      'last_seen' => $status['last_seen'] ?? NULL,
    ]);
  }

}