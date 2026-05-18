<?php

namespace Drupal\citius_user\Hook;

use Drupal\citius_user\CurrentCenterResolver;
use Drupal\citius_user\UserFields;
use Drupal\citius_user\UserRoles;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Adjust access to user entities.
 */
class UserAccess {

  public function __construct(
    protected CurrentCenterResolver $currentCenterResolver,
  ) {}

  /**
   * Implements hook_user_access().
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity to check access for.
   * @param string $operation
   *   The operation being checked (e.g., 'view', 'edit', 'delete').
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to evaluate access permissions for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of the access check.
   */
  #[Hook('user_access')]
  public function userAccess(UserInterface $user, string $operation, AccountInterface $account): AccessResultInterface {
    if (in_array($operation, ['update', 'delete'], TRUE)
      && !$account->hasPermission('administer permissions')
      && $user->id() !== $account->id()
    ) {
      $roles = [
        UserRoles::PHYSIOTHERAPIST,
        UserRoles::PATIENT,
        UserRoles::MANAGER,
      ];
      $current_center = $this->currentCenterResolver->get();
      foreach ($roles as $role) {
        $permission = 'create ' . $role . ' user';
        if ($user->hasRole($role) && $account->hasPermission($permission)) {
          if ($current_center) {
            $user_centers = $user->get(UserFields::CENTER)->getValue();
            $user_centers = array_column($user_centers, 'target_id');
            return AccessResult::allowedIf(in_array($current_center, $user_centers, FALSE));
          }
          return AccessResult::allowed();
        }
      }
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

}
