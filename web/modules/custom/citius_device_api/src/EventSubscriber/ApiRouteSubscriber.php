<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class ApiRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach ($collection as $name => $route) {
      if (str_starts_with($name, 'rest.citius_device_api')) {
        $requirements = $route->getRequirements();
        unset($requirements['_csrf_request_header_token']);
        $route->setRequirements($requirements);
      }
    }
  }

}
