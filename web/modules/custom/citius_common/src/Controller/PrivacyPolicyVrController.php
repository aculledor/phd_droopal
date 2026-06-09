<?php

namespace Drupal\citius_common\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides the VR application privacy policy page.
 */
class PrivacyPolicyVrController extends ControllerBase {

  /**
   * Builds the VR application privacy policy page.
   *
   * @return array
   *   A render array for the privacy policy page.
   */
  public function page(): array {
    return [
      '#theme' => 'privacy_policy_vr',
      '#cache' => [
        'max-age' => 86400,
      ],
    ];
  }

}
