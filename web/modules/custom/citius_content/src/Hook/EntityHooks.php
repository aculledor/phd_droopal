<?php

namespace Drupal\citius_content\Hook;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\citius_content\NodeBundles;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks related to entities.
 */
class EntityHooks {

  /**
   * Implements hook_entity_bundle_info_alter().
   */
  #[Hook('entity_bundle_info_alter')]
  public function entityBundleInfoAlter(array &$bundles): void {
    if (isset($bundles['node'][NodeBundles::SESSION])) {
      $bundles['node'][NodeBundles::SESSION]['class'] = SessionNode::class;
    }
  }

}
