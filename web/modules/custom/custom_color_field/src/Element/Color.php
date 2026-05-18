<?php

namespace Drupal\custom_color_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a form element for a set of radio buttons.
 */
#[FormElement('custom_color')]
class Color extends Radios {

  /**
   * Get colors.
   *
   * @return array[]
   *   Array with colors.
   */
  public static function getColors(): array {
    $colors = self::getDefaultColors();

    \Drupal::moduleHandler()
      ->invokeAll('custom_color_list_colors_alter', [&$colors]);

    $colors['transparent'] = [
      'title' => t('Transparent'),
      'code'  => 'rgba(0,0,0,0)',
    ];

    return $colors;
  }

  /**
   * Check if the color it's valid, or it was removed in a hook.
   *
   * @param string $colorValue
   *   Color value.
   *
   * @return bool
   *   True if it's valid.
   */
  public static function isValidColor(string $colorValue): bool {
    $colors = self::getColors();
    return isset($colors[$colorValue]);
  }

  /**
   * Get default colors.
   *
   * @return array[]
   *   Array with colors.
   */
  public static function getDefaultColors(): array {
    return [
      'black'     => [
        'title' => t('Black'),
        'code'  => '#000000',
      ],
      'blue'      => [
        'title' => t('Blue'),
        'code'  => '#007bc4',
      ],
      'dark_blue' => [
        'title' => t('Dark blue'),
        'code'  => '#002b4a',
      ],
      'blue-50'   => [
        'title' => t('Blue 50%'),
        'code'  => '#7fbce1',
      ],
      'blue-25'   => [
        'title' => t('Blue 25%'),
        'code'  => '#bfddf0',
      ],
      'blue-10'   => [
        'title' => t('Blue 10%'),
        'code'  => '#e5f1f8',
      ],
      'gray'      => [
        'title' => t('Gray'),
        'code'  => '#e9ecef',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();

    $options = array_map(static function ($item) {
      return $item['title'];
    }, self::getColors());

    $info['#options'] = $options;
    $info['#attributes']['class'] = ['custom-color-selector-widget'];

    return $info;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processRadios($element, $form_state, $complete_form);
    $element['#attached']['library'][] = 'custom_color_field/widget';
    $colors = self::getColors();

    if (count($element['#options']) > 0) {
      foreach ($element['#options'] as $key => $choice) {
        if (self::isValidColor($key)) {
          $element[$key]['#wrapper_attributes']['style'] = 'background-color:' . $colors[$key]['code'];
          $element[$key]['#wrapper_attributes']['title'] = $colors[$key]['title'];
        }
        else {
          $element[$key]['#access'] = FALSE;
        }
      }
    }
    return $element;
  }

}
