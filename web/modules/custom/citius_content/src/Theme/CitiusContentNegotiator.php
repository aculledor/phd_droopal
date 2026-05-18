<?php

declare(strict_types=1);

namespace Drupal\citius_content\Theme;

use Drupal\citius_content\NodeBundles;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Defines a theme negotiator that deals with the active theme on example page.
 */
final readonly class CitiusContentNegotiator implements ThemeNegotiatorInterface {

  public function __construct(
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    $route_name = $route_match->getRouteName();
    if (in_array($route_name, ['node.add', 'entity.node.edit_form', 'quick_node_clone.node.quick_clone'])) {
      $bundle = $route_match->getParameter('node_type');
      if (!$bundle instanceof NodeTypeInterface) {
        $node = $route_match->getParameter('node');
        if (!$node instanceof NodeInterface) {
          return FALSE;
        }
        $bundle = $node->bundle();
      }
      else {
        $bundle = $bundle->id();
      }
      return in_array(
        $bundle,
        [
          NodeBundles::CENTER,
          NodeBundles::DEVICE,
          NodeBundles::EXERCISE,
          NodeBundles::ROUTINE,
          NodeBundles::SESSION,
        ],
        TRUE
      );
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match): ?string {
    return $this->configFactory->get('system.theme')->get('default');
  }

}
