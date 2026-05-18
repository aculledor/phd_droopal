<?php

namespace Drupal\citius_user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves selected center.
 */
class CurrentCenterResolver {

  private const string SESSION_KEY = 'citius_user_current_center';

  public function __construct(
    protected RequestStack $requestStack,
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Get currently selected center.
   *
   * @return int|null
   *   Center ID opr null.
   */
  public function get(): ?int {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return NULL;
    }
    $session = $request->getSession();
    $center = $session->get(self::SESSION_KEY);
    $id = $this->currentUser->id();
    if (!$center && $id) {
      $user = $this->entityTypeManager->getStorage('user')->load($id);
      if ($user instanceof UserInterface) {
        /** @var \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<\Drupal\node\NodeInterface>> $user_centers */
        $user_centers = $user->get(UserFields::CENTER);
        if ($user_centers->count() === 1) {
          $center = $user_centers->first()?->target_id;
          if (!$center) {
            return NULL;
          }
          $center = (int) $center;
          $session->set(self::SESSION_KEY, $center);
          return $center;
        }
      }
    }
    return $center;
  }

  /**
   * Store selected center in session.
   *
   * @param int $center
   *   Center ID.
   */
  public function set(int $center): void {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return;
    }
    $request->getSession()->set(self::SESSION_KEY, $center);
  }

}
