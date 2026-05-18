<?php

declare(strict_types = 1);

namespace Drupal\phone_international\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a phone_international form.
 *
 * Usage example:
 *
 * By default field has geolocation enable.
 *
 * @code
 * $form['phone'] = [
 *   '#type' => 'phone_international',
 *   '#title' => $this->t('International Phone'),
 * ];
 * @endcode
 *
 * If you want default country you need to do this:
 *
 * @code
 * $form['phone'] = [
 *   '#type' => 'phone_international',
 *   '#title' => $this->t('International Phone'),
 *   '#attributes' => [
 *      'data-country' => 'PT',
 *      'data-geo' => 0, // 0(Disable) or 1(Enable)
 *      'data-exclude' => [],
 *      'data-only' => [],
 *      'data-preferred' => ['PT']
 *   ],
 * ];
 * @endcode
 *
 * @FormElement("phone_international")
 */
class PhoneInternationalElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, mixed $input, FormStateInterface $form_state): mixed {
    if (empty($input['full_number'])) {
      return '';
    }

    return $input['full_number'];
  }

  /**
   * Form element validation handler for #type 'phone_international'.
   *
   * @param array $element
   *   A form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The $form_state from complete form.
   * @param array $complete_form
   *   Complete parent form.
   */
  public static function validateNumber(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $value = $element['#value'];
    $form_state->setValueForElement($element, $value);

    if (!empty($value) && !\Drupal::service('phone_international.validate')->isValidNumber($value)) {
      $form_state->setError($element, t('The %name "%phone_international" is not valid.', [
        '%phone_international' => $value,
        '%name' => $element['#title'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#input' => TRUE,
      '#process' => [
        [$this, 'processInternationalPhone'],
      ],
      '#element_validate' => [
        [$this, 'validateNumber'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#tree' => TRUE,
      '#country' => '',
      '#geolocation' => 0,
      '#exclude_countries' => [],
      '#countries' => 'exclude',
      '#preferred_countries' => [],
      '#default_value' => '',
    ];
  }

  /**
   * Adds tel and hidden input to phone_international element.
   *
   * @param array $element
   *   A form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The $form_state from complete form.
   * @param array $complete_form
   *   Complete parent form.
   *
   * @return array
   *   Returns the element with the configured widget settings.
   */
  public static function processInternationalPhone(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $element['#attached']['library'][] = 'phone_international/phone_international';

    if ($element['#countries'] != 'all'
      && !empty($element['#exclude_countries'])
      && ($element['#countries'] === 'exclude'
      && in_array($element['#country'], $element['#exclude_countries']))
      || ($element['#countries'] === 'include'
      && !in_array($element['#country'], $element['#exclude_countries']))
    ) {
      $element['#country'] = reset($element['#exclude_countries']);
    }

    $element['int_phone'] = [
      '#type' => 'tel',
      '#default_value' => $element['#default_value'],
      '#attributes' => [
        'class' => ['phone_international-number'],
        'data-country' => (int) $element['#geolocation'] === 1 ? 'auto' : $element['#country'],
        'data-geo' => (int) $element['#geolocation'],
        'data-preferred' => $element['#preferred_countries'] ? implode("-", $element['#preferred_countries']) : [],
      ],
      '#theme_wrappers' => [],
      '#size' => 30,
      '#maxlength' => 128,
    ];

    // Limit countries using either `data-only` or `data-exclude`.
    $key = ($element['#countries'] === 'include' ? 'data-only' : 'data-exclude');
    $element['int_phone']['#attributes'][$key] = !empty($element['#exclude_countries']) ? implode("-", $element['#exclude_countries']) : [];

    $path = base_path() . _phone_international_get_path();

    if (\Drupal::config('phone_international.settings')->get('cdn')) {
      $path = '//cdn.jsdelivr.net/npm/intl-tel-input/build';
    }

    $element['#attached']['drupalSettings']['phone_international']['path'] = $path;

    $element['full_number'] = [
      '#type' => 'hidden',
    ];

    if (!empty($element['#value'])) {
      $element['int_phone']['#value'] = $element['#value'];
      $element['full_number']['#value'] = $element['#value'];
    }

    if (!empty($element['#default_value'])) {
      $element['full_number']['#default_value'] = $element['#default_value'];
    }

    return $element;
  }

}
