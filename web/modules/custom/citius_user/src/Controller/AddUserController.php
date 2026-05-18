<?php

declare(strict_types=1);

namespace Drupal\citius_user\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add user form.
 */
final class AddUserController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_route_match'),
    );
  }

  /**
   * Access control handler.
   *
   * @param string $role
   *   Role ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Access result.
   */
  public function access(string $role): AccessResultInterface {
    $permission = 'create ' . $role . ' user';
    return AccessResult::allowedIf($this->currentUser()->hasPermission($permission));
  }

  /**
   * Get route title.
   *
   * @param string $role
   *   Role ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Label for the page.
   */
  public function title(string $role): TranslatableMarkup {
    $role_entity = $this->entityTypeManager()->getStorage('user_role')->load($role);
    $role_label = $role_entity?->label() ?? $role;
    if ($role === 'user') {
      return $this->t('New user');
    }
    return $this->t('New @role', ['@role' => mb_strtolower((string) $role_label)]);
  }

  /**
   * Generates controller response.
   *
   * @param string $role
   *   Role ID.
   *
   * @return array
   *   Render array.
   */
  public function __invoke(string $role): array {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager()->getStorage('user')->create([
      'status' => 1,
    ]);
    if ($role !== 'user') {
      $user->addRole($role);
    }

    return $this->entityFormBuilder()->getForm($user);
  }

}
