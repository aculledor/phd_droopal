<?php

namespace Drupal\citius_user\Hook;

use Drupal\block\Entity\Block;
use Drupal\citius_user\UserFields;
use Drupal\citius_user\UserRoles;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Common hooks for user entities.
 */
class UserHooks {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Implements hook_user_format_name_alter().
   *
   * @param string $name
   *   Username.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   User account.
   */
  #[Hook('user_format_name_alter')]
  public function formatNameAlter(string &$name, AccountInterface $user): void {
    if ($user->isAuthenticated()) {
      /** @var \Drupal\user\UserInterface $user_entity */
      $user_entity = $this->entityTypeManager->getStorage('user')->load($user->id());
      $first_name = $user_entity->get(UserFields::NAME)->value;
      $last_name = $user_entity->get(UserFields::SURNAME)->value;
      $parts = array_filter([$first_name, $last_name]);
      if (!empty($parts)) {
        $name = implode(' ', $parts);
      }
    }
  }

  /**
   * Implements hook_block_access().
   */
  #[Hook('block_access')]
  public function patientSessionsBlockAccess(Block $block, string $operation, AccountInterface $account): AccessResultInterface {
    if ($block->getPluginId() === 'views_block:user_sessions-pending_sessions') {
      $route_name = $this->routeMatch->getRouteName();
      if ($route_name !== 'entity.user.canonical') {
        return AccessResult::forbidden();
      }
      $user = $this->routeMatch->getParameter('user');
      if ($user instanceof UserInterface) {
        return AccessResult::forbiddenIf(!$user->hasRole(UserRoles::PATIENT));
      }
    }

    return AccessResult::neutral();
  }

}
