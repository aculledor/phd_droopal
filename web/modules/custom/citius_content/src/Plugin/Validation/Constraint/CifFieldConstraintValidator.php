<?php

namespace Drupal\citius_content\Plugin\Validation\Constraint;

use IsoCodes\Cif;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is a valid cIF.
 */
class CifFieldConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$item = $value->first()) {
      return;
    }
    if (!Cif::validate($item->value)) {
      /** @var \Drupal\citius_content\Plugin\Validation\Constraint\CifFieldConstraint $constraint */
      $this->context->addViolation($constraint->message, [
        '%value' => $item->value,
        '@field_name' => mb_strtolower($value->getFieldDefinition()
          ->getLabel()),
      ]);
    }
  }

}
