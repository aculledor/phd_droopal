<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\EventSubscriber;

use Drupal\citius_device_api\DeviceTokenManager;
use Drupal\citius_device_api\ExercisePayloadStorage;
use Drupal\Component\Serialization\Json;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribe on request events.
 */
final class RequestSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected ExercisePayloadStorage $exercisePayloadStorage,
    protected DeviceTokenManager $deviceTokenManager,
  ) {}

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Request event.
   */
  public function onKernelRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    if ($request->getPathInfo() === '/api/glass/register') {
      $request->headers->set('Content-Type', 'application/json');
    }

    if (!$event->isMainRequest() || $request->getPathInfo() !== '/api/exercise' || $request->getMethod() !== 'POST') {
      return;
    }

    $raw_body = $request->getContent() ?: '';
    $decoded_data = NULL;
    if ($raw_body !== '') {
      $decoded = Json::decode($raw_body);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $decoded_data = $decoded;
      }
    }

    try {
      $raw_payload_id = $this->exercisePayloadStorage->createRawPayload(
        $raw_body,
        $decoded_data,
        $this->getDeviceIdFromAuthorizationHeader($request->headers->get('Authorization')),
      );
      $request->attributes->set('citius_device_api_raw_payload_id', $raw_payload_id);
    }
    catch (\Throwable $exception) {
       // Never block the Unity app because the defensive raw log failed.
      \Drupal::logger('citius_device_api')->error('Raw payload logging failed: @error', [
        '@error' => $exception->getMessage(),
      ]);
    }
  }

  /**
   * Gets the device id encoded in a bearer token without requiring validity.
   */
  protected function getDeviceIdFromAuthorizationHeader(?string $header): ?string {
    if (!$header || !str_starts_with($header, 'Bearer ')) {
      return NULL;
    }
    return $this->deviceTokenManager->getDeviceIdFromToken(substr($header, 7));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 1000],
    ];
  }

}
