<?php

declare(strict_types=1);

namespace Drupal\citius_user\Plugin\Block;

use Drupal\citius_user\CurrentCenterResolver;
use Drupal\citius_user\Form\ChangeCenterForm;
use Drupal\citius_user\UserFields;
use Drupal\citius_user\UserRoles;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a current center block.
 */
#[Block(
  id: 'current_center_block',
  admin_label: new TranslatableMarkup('Current center block'),
  category: new TranslatableMarkup('CITIUS'),
)]
final class CurrentCenterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly CurrentCenterResolver $currentCenterResolver,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxy $currentUser,
    private readonly FormBuilderInterface $formBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(CurrentCenterResolver::class),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $build = [
      '#attributes' => ['class' => [Html::getClass($this->getPluginId())]],
    ];
    if ($user && $user->get(UserFields::CENTER)->count() > 1) {
      $build['content'] = $this->formBuilder->getForm(ChangeCenterForm::class);
    }
    else {
      $center = $this->entityTypeManager->getStorage('node')->load($this->currentCenterResolver->get());
      $build['content'] = ['#markup' => $center?->label() ?? ''];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    return AccessResult::allowedIf($this->currentUser->hasRole(UserRoles::PHYSIOTHERAPIST) && $this->currentCenterResolver->get());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $cache_contexts = parent::getCacheContexts();
    $cache_contexts[] = 'session';
    return $cache_contexts;
  }

}
