<?php

namespace Drupal\citius_content\Hook;

use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\NodeFields;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Content hooks.
 */
class ContentHooks {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   *
   * @param array $field_widget_complete_form
   *   Complete form for the field widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $context
   *   Context.
   */
  #[Hook('field_widget_complete_paragraphs_form_alter')]
  public function paragraphsWidgetAlter(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    if (isset($field_widget_complete_form['widget']['add_more']['operations']['#links'])) {
      $links = $field_widget_complete_form['widget']['add_more']['operations']['#links'];
      uasort($links, static fn($a, $b) => (string) $a['title']['#value'] <=> (string) $b['title']['#value']);
      $field_widget_complete_form['widget']['add_more']['operations']['#links'] = $links;
    }
  }

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    if ($entity instanceof NodeInterface
      && $entity->bundle() === NodeBundles::ROUTINE
      && in_array($operation, ['update', 'delete'])) {
      $sessions = $this->entityTypeManager
        ->getStorage('node')
        ->loadByProperties([
          'type' => NodeBundles::SESSION,
          NodeFields::ROUTINE => $entity->id(),
        ]);
      return AccessResult::forbiddenIf(!empty($sessions));
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_node_exercise_edit_form_alter')]
  public function exerciseEditFormAlter(array &$form, FormStateInterface $form_state): void {
    $form[NodeFields::TYPE]['widget']['#disabled'] = TRUE;
  }

}
