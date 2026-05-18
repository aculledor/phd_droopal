<?php

declare(strict_types = 1);

namespace Drupal\phone_international\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'phone_international' field type.
 *
 * @FieldType(
 *   id = "phone_international",
 *   label = @Translation("International phone"),
 *   description = @Translation("Stores an International phone."),
 *   default_widget = "phone_international_widget",
 *   default_formatter = "phone_international_formatter",
 * )
 */
class PhoneInternational extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    $this->value = \Drupal::service('phone_international.validate')
      ->formatNumber($this->get('value')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('International Phone'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 256,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $values['value'] = rand(pow(10, 8), pow(10, 9) - 1);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $value = $this->get('value')->getValue();

    return $value === NULL || $value === '';
  }

}
