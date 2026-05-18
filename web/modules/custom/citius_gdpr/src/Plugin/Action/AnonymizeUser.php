<?php

declare(strict_types=1);

namespace Drupal\citius_gdpr\Plugin\Action;

use Drupal\citius_gdpr\GdprUserFields;
use Drupal\citius_user\UserRoles;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStore;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Anonymize user action.
 */
#[Action(
  id: 'anonymize_user',
  label: new TranslatableMarkup('Anonymize user'),
  category: new TranslatableMarkup('CITIUS'),
  confirm_form_route_name: 'citius_gdpr.anonymize_confirm',
  type: 'user',
)]
class AnonymizeUser extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $tempStore;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->tempStore = $container->get('tempstore.private')->get('citius_gdpr_anonymize_confirm');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function access($entity, ?AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface|bool {
    /** @var \Drupal\user\UserInterface $entity */
    $access = $entity->access('update', $account, TRUE)
      ->andIf(AccessResult::allowedIf($entity->get(GdprUserFields::DATE)->isEmpty() && $entity->hasRole(UserRoles::PATIENT)));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities): void {
    $ids = [];
    foreach ($entities as $entity) {
      $ids[] = $entity->id();
    }
    $this->tempStore->set('ids', $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(?ContentEntityInterface $entity = NULL): void {
    if ($entity) {
      $this->executeMultiple([$entity]);
    }
  }

}
