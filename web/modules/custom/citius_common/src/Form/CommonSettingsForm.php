<?php

namespace Drupal\citius_common\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the common settings form.
 */
class CommonSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   *
   * @return string
   *   The unique ID of the form.
   */
  public function getFormId(): string {
    return 'common_admin_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The editable configuration names.
   */
  protected function getEditableConfigNames(): array {
    return [
      'citius_common.settings',
    ];
  }

  /**
   * Defines the form.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form elements.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Common settings'),
      '#description' => $this->t('Configure common settings for the site.'),
    ];

    $config = $this->config('citius_common.settings');

    $form['settings']['address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#default_value' => $config->get('address'),
    ];

    $form['settings']['copyright'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Copyright'),
      '#default_value' => $config->get('copyright'),
    ];

    return $form;
  }

  /**
   * Defines the submit form.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('citius_common.settings')
      ->set('address', $form_state->getValue('address'))
      ->set('copyright', $form_state->getValue('copyright'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
