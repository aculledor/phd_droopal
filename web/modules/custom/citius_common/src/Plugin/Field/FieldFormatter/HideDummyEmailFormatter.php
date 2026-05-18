<?php

declare(strict_types=1);

namespace Drupal\citius_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'Hide dummy email' formatter.
 *
 * @extends \Drupal\Core\Field\FormatterBase<\Drupal\Core\Field\FieldItemListInterface>
 */
#[FieldFormatter(
  id: 'hide_dummy_email',
  label: new TranslatableMarkup('Hide dummy email'),
  field_types: ['email'],
)]
class HideDummyEmailFormatter extends FormatterBase {

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\Plugin\Field\FieldType\EmailItem> $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $value = (string) $item->value;
      if (str_ends_with($value, '@citius.dummy')) {
        $value = '-';
      }
      $elements[$delta] = [
        '#markup' => $value,
      ];
    }

    return $elements;
  }

}
