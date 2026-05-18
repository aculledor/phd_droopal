<?php

namespace Drupal\citius_content\Hook;

use Drupal\citius_content\NodeFields;
use Drupal\citius_user\UserRoles;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\NodeForm;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Alter node form.
 */
class NodeFormAlter {

  public function __construct(
    #[Autowire(service: 'current_user')]
    protected AccountProxy $currentUser,
  ) {}

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_node_device_form_alter')]
  #[Hook('form_node_device_edit_form_alter')]
  #[Hook('form_node_routine_form_alter')]
  #[Hook('form_node_routine_edit_form_alter')]
  #[Hook('form_node_routine_quick_node_clone_form_alter')]
  #[Hook('form_node_exercise_form_alter')]
  #[Hook('form_node_exercise_edit_form_alter')]
  #[Hook('form_node_center_form_alter')]
  #[Hook('form_node_center_edit_form_alter')]
  #[Hook('form_node_session_form_alter')]
  #[Hook('form_node_session_edit_form_alter')]
  public function nodeFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $form['advanced']['#weight'] = 1000;
    if (!$this->currentUser->hasRole(UserRoles::ADMINISTRATOR)) {
      $form['advanced']['#access'] = FALSE;
      foreach (Element::children($form) as $key) {
        $group = $form[$key]['#group'] ?? NULL;
        if ($group === 'advanced') {
          $form[$key]['#access'] = FALSE;
        }
      }
    }
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof NodeForm) {
      $node = $form_object->getEntity();
      $form['#attributes']['class'][] = Html::cleanCssIdentifier('node-form__' . $node->bundle());
    }
  }

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_paragraphs_form_alter')]
  public function exerciseParagraphsFormAlter(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList<\Drupal\paragraphs\ParagraphInterface> $items */
    $items = $context['items'];
    $field_name = $items->getFieldDefinition()->getName();
    if ($field_name !== NodeFields::EXERCISES) {
      return;
    }
    foreach ($field_widget_complete_form['widget'] as $key => $item) {
      if (!is_numeric($key)) {
        continue;
      }
      foreach ($item['top']['actions']['dropdown_actions'] as $action_key => $action) {
        $field_widget_complete_form['widget'][$key]['top']['actions']['actions'][$action_key] = $action;
      }
      unset($field_widget_complete_form['widget'][$key]['top']['actions']['dropdown_actions']);
      foreach ($field_widget_complete_form['widget'][$key]['top']['actions']['actions'] as $action_key => $action) {
        $field_widget_complete_form['widget'][$key]['top']['actions']['actions'][$action_key]['#attributes']['class'][] = 'button--only-icon';
        $field_widget_complete_form['widget'][$key]['top']['actions']['actions'][$action_key]['#attributes']['class'][] =
          Html::cleanCssIdentifier('icon-' . str_replace('_button', '', $action_key));
        if (isset($field_widget_complete_form['widget'][$key]['top']['actions']['actions'][$action_key]['#value'])) {
          $field_widget_complete_form['widget'][$key]['top']['actions']['actions'][$action_key]['#attributes']['title'] =
            $field_widget_complete_form['widget'][$key]['top']['actions']['actions'][$action_key]['#value'];
        }
      }
    }
  }

}
