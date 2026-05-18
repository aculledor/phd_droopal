<?php

declare(strict_types = 1);

namespace Drupal\phone_international\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form with setting for phone international.
 */
class PISettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'phone_international_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'phone_international.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('phone_international.settings');
    $form['cdn'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('cdn'),
      '#title' => $this->t('Enable CDN Libraries'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('phone_international.settings')
      ->set('cdn', (bool) $form_state->getValue('cdn'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
