<?php

declare(strict_types=1);

namespace Drupal\citius_content\EventSubscriber;

use Drupal\citius_content\NodeBundles;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('node.add')) {
      $route->setDefault('_title_callback', [self::class, 'nodeAddTitleCallback']);
    }
  }

  /**
   * Title for node add form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   Node type object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Route title.
   */
  public static function nodeAddTitleCallback(NodeTypeInterface $node_type): TranslatableMarkup {
    $node_type_name = (string) $node_type->label();
    if ($node_type->id() === NodeBundles::ROUTINE) {
      return t('New routine');
    }
    return t('New @node_type', ['@node_type' => strtolower($node_type_name)]);
  }

}
