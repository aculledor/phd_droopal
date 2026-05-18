<?php

declare(strict_types=1);

namespace Drupal\citius_common\Plugin\Block;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\citius_content\NodeFields;
use Drupal\citius_user\UserRoles;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a back link block.
 */
#[Block(
  id: 'citius_common_back_link',
  admin_label: new TranslatableMarkup('Back link'),
  category: new TranslatableMarkup('CITIUS'),
)]
class BackLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Static variable to store the back link.
   *
   * @var \Drupal\Core\Link|null
   */
  protected static ?Link $backLink = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $route_name = $this->routeMatch->getRouteName();
    $build['content'] = [
      '#markup' => $route_name,
    ];
    $link = $this->getBackLink();
    if ($link) {
      $renderable = $link->toRenderable();
      $renderable['#attributes']['class'][] = 'back-link';
      $build['content'] = $renderable;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    return AccessResult::allowedIf($this->getBackLink() !== NULL);
  }

  /**
   * Get back link for current route.
   *
   * @return \Drupal\Core\Link|null
   *   Back link.
   */
  protected function getBackLink(): ?Link {
    if (static::$backLink === NULL) {
      $route_name = $this->routeMatch->getRouteName();
      $link = match ($route_name) {
        'entity.user.canonical', 'view.user_sessions.executed_sessions' => $this->getPatientBackLink(),
        'entity.node.canonical' => $this->getNodeBackLink(),
        'view.performance_chart.performance_chart' => $this->getPerformanceChartBackLink(),
        default => NULL,
      };
      static::$backLink = $link;
    }
    return static::$backLink;
  }

  /**
   * Get back link for patients' profile.
   *
   * @return \Drupal\Core\Link|null
   *   Back link for patients' profile.
   */
  protected function getPatientBackLink(): ?Link {
    $user = $this->routeMatch->getParameter('user');
    if (is_numeric($user)) {
      $user = $this->entityTypeManager->getStorage('user')->load($user);
    }
    if ($user instanceof UserInterface && $user->hasRole(UserRoles::PATIENT)) {
      return Link::createFromRoute(
        $this->t('Patients list'),
        'view.users_list.patients',
      );
    }
    return NULL;
  }

  /**
   * Get back link for node page.
   *
   * @return \Drupal\Core\Link|null
   *   Link.
   */
  protected function getNodeBackLink(): ?Link {
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof SessionNode) {
      $patient = $node->get(NodeFields::PATIENT)->entity;
      if ($patient instanceof UserInterface && $patient->hasRole(UserRoles::PATIENT)) {
        return Link::createFromRoute(
          $patient->getDisplayName(),
          'entity.user.canonical',
          ['user' => $patient->id()],
        );
      }
    }
    return NULL;
  }

  /**
   * Get backlink from performance chart.
   *
   * @return \Drupal\Core\Link|null
   *   Backlink to user profile.
   */
  protected function getPerformanceChartBackLink(): ?Link {
    $user = $this->routeMatch->getParameter('user');
    if (is_numeric($user)) {
      $user = $this->entityTypeManager->getStorage('user')->load($user);
    }
    if ($user instanceof UserInterface && $user->hasRole(UserRoles::PATIENT)) {
      return Link::createFromRoute(
        $user->getDisplayName(),
        'entity.user.canonical',
        ['user' => $user->id()],
      );
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return array_merge(parent::getCacheContexts(), ['url']);
  }

}
