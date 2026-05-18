<?php

namespace Drupal\citius_common\Hook;

use Drupal\citius_content\NodeFields;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Contains common hooks.
 */
class CommonHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'pager_with_summary' => [
        'variables' => [
          'pager' => NULL,
          'summary' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    $field_name = NodeFields::CENTER;
    $data['user__' . $field_name][$field_name . '_target_id']['filter']['id'] = 'entity_reference';

    foreach ($data as $table_name => $table_data) {
      if (!empty($table_data['table']['entity type']) && isset($table_data['table']['base']['field'])) {
        $data[$table_name]['entity_operation_icon_buttons'] = [
          'title' => $this->t('Operation icon buttons'),
          'help' => $this->t('Displays entity operation buttons as icons.'),
          'field' => [
            'id' => 'entity_operation_icon_buttons',
          ],
        ];
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_views_exposed_form_alter')]
  public function viewsExposedFormAlter(array &$form, FormStateInterface $form_state): void {
    if (isset($form['actions']['reset'])) {
      $form['actions']['reset']['#attributes']['class'][] = 'button--secondary';
      $form['actions']['reset']['#attributes']['class'][] = 'button--icon';
      $form['actions']['reset']['#attributes']['class'][] = 'button--icon-reset';
      $form['actions']['reset']['#attributes']['class'][] = 'form-reset';
    }
    if (isset($form['actions']['submit'])) {
      $form['actions']['submit']['#attributes']['class'][] = 'button--icon';
      $form['actions']['submit']['#attributes']['class'][] = 'button--icon-search';
    }
    if (isset($form['date_wrapper']['date_wrapper']['date']['min'], $form['date_wrapper']['date_wrapper']['date']['max'])) {
      unset($form['date_wrapper']['#title'], $form['date_wrapper']['date_wrapper']['#title']);
      $form['date_wrapper']['date_wrapper']['date']['min']['#title'] = $this->t('Date from');
      $form['date_wrapper']['date_wrapper']['date']['max']['#title'] = $this->t('Date to');
      $form['date_wrapper']['#attributes']['class'][] = 'form-date-wrapper';
    }
  }

}
