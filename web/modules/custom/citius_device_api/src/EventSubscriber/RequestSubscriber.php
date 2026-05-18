<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribe on request events.
 */
final class RequestSubscriber implements EventSubscriberInterface {

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
