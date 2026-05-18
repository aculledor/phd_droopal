<?php

declare(strict_types = 1);

namespace Drupal\phone_international\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a phone field mapper.
 *
 * @FeedsTarget(
 *   id = "phone_international",
 *   field_types = {"phone_international"}
 * )
 */
class PhoneInternational extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value');
  }

}
