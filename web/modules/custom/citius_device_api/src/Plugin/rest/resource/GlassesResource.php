<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\rest\resource;

use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\NodeFields;
use Drupal\citius_content\SessionState;
use Drupal\citius_content\TaxonomyBundles;
use Drupal\citius_content\TaxonomyFields;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\ResourceResponseInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns session list for specified glasses.
 */
#[RestResource(
  id: 'citius_device_api_glasses',
  label: new TranslatableMarkup('Glass sessions'),
  uri_paths: [
    'canonical' => '/api/glass',
  ],
)]
class GlassesResource extends ApiResourceBase {

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponseInterface
   *   The response.
   */
  public function get(): ResourceResponseInterface {
    if (!$this->isAuthenticated()) {
      throw new AccessDeniedHttpException('Authentication required.');
    }
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      throw new NotFoundHttpException();
    }
    $id = $request->get('id');
    if (!$id) {
      throw new BadRequestHttpException('ID is not provided.');
    }
    $nid = $this->getValidGlassesNid($id);
    if (!$nid) {
      throw new NotFoundHttpException('Invalid device id.');
    }
    $session_states = [
      SessionState::Scheduled->value,
      SessionState::Execution->value,
      SessionState::Pause->value,
    ];
    $sessions = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'type' => NodeBundles::SESSION,
        NodeFields::GLASSES => $nid,
        NodeFields::SESSION_STATE => $session_states,
      ]);

    return new ModifiedResourceResponse([
      'metadata' => $this->getMetadata(),
      'unity_session_routines' => array_values($sessions),
    ]);
  }

  /**
   * Checks if the device is glasses.
   *
   * @param string $id
   *   The device id.
   *
   * @return int|null
   *   Glasses node ID or NULL.
   */
  protected function getValidGlassesNid(string $id): ?int {
    $devices = $this->entityTypeManager->getStorage('node')
      ->loadByProperties([
        'type' => NodeBundles::DEVICE,
        NodeFields::CODE => $id,
      ]);
    $device = reset($devices);
    if (!$device instanceof NodeInterface || $device->bundle() !== NodeBundles::DEVICE) {
      return NULL;
    }
    $model = $device->get(NodeFields::MODEL)->entity;
    if (!$model instanceof TermInterface || $model->bundle() !== TaxonomyBundles::MODEL) {
      return NULL;
    }
    $device_type = $model->get(TaxonomyFields::TYPE)->entity;
    if (!$device_type instanceof TermInterface
      || $device_type->bundle() !== TaxonomyBundles::DEVICE_TYPE) {
      return NULL;
    }
    return $device_type->get(TaxonomyFields::CODE)->value === 'glass' ? (int) $device->id() : NULL;
  }

}
