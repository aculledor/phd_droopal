<?php

namespace Drupal\citius_content\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if an entity field is a CIF valid value.
 */
#[Constraint(
  id: 'CifField',
  label: new TranslatableMarkup('CIF field constraint'),
)]
class CifFieldConstraint extends SymfonyConstraint {

  /**
   * Error message.
   *
   * @var string
   */
  public string $message = '"%value" is not a valid CIF.';

}
