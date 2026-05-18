<?php

declare(strict_types=1);

namespace Drupal\citius_gdpr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Citius GDPR settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'citius_gdpr_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['citius_gdpr.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('citius_gdpr.settings');

    $form['apply_gdpr'] = [
      '#type' => 'select',
      '#title' => $this->t('Apply GDPR'),
      '#default_value' => $config->get('apply_gdpr') ?? 5,
      '#suffix' => $this->t('years'),
      '#options' => array_combine(range(1, 20), range(1, 20)),
      '#description' => $this->t('Select the number of years to apply GDPR'),
      '#required' => TRUE,
    ];

    $form['cancellation_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Cancellation period'),
      '#default_value' => $config->get('cancellation_period') ?? 30,
      '#suffix' => $this->t('days'),
      '#options' => array_combine(range(1, 30), range(1, 30)),
      '#description' => $this->t('Period of time where admin can cancel user account anonymization.'),
      '#required' => TRUE,
    ];

    $form['female_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Female name'),
      '#default_value' => $config->get('female_name') ?? '',
      '#description' => $this->t('Female name to use for anonymized users.'),
      '#required' => TRUE,
    ];

    $form['male_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Male name'),
      '#default_value' => $config->get('male_name') ?? '',
      '#description' => $this->t('Male name to use for anonymized users.'),
      '#required' => TRUE,
    ];

    $form['surname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Surname'),
      '#default_value' => $config->get('surname') ?? '',
      '#description' => $this->t('Surname to use for anonymized users.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('citius_gdpr.settings')
      ->set('apply_gdpr', $form_state->getValue('apply_gdpr'))
      ->set('cancellation_period', $form_state->getValue('cancellation_period'))
      ->set('female_name', $form_state->getValue('female_name'))
      ->set('male_name', $form_state->getValue('male_name'))
      ->set('surname', $form_state->getValue('surname'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
