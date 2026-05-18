<?php

namespace Drupal\json_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'pretty' formatter.
 *
 * @FieldFormatter(
 *   id = "pretty",
 *   label = @Translation("Pretty"),
 *   field_types = {
 *     "json",
 *     "json_native",
 *     "json_native_binary",
 *   },
 * )
 */
class PrettyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'json_pretty',
        '#json' => json_decode($item->value),
      ];
    }
    return $elements;
  }

}
