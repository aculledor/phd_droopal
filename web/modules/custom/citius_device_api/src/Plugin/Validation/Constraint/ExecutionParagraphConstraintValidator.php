<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\Validation\Constraint;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\citius_content\ParagraphBundles;
use Drupal\citius_device_api\Entity\ExecutionInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that execution paragraphs belong to the specified session.
 */
final class ExecutionParagraphConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof ExecutionParagraphConstraint) {
      throw new \InvalidArgumentException(
        sprintf('The constraint must be instance of \Drupal\citius_device_api\Plugin\Validation\Constraint\ExecutionParagraphConstraint, %s was given.', get_debug_type($constraint))
      );
    }
    if (!$value instanceof ExecutionInterface) {
      throw new \InvalidArgumentException(
        sprintf('The validated value must be instance of \Drupal\Core\Entity\EntityInterface, %s was given.', get_debug_type($value))
      );
    }
    $exercise = $value->getExercise();
    if (!$exercise instanceof ParagraphInterface) {
      $this->context->addViolation($constraint->emptyMessage);
      return;
    }
    if ($exercise->bundle() !== ParagraphBundles::EXERCISE) {
      $this->context->addViolation($constraint->wrongBundleMessage);
      return;
    }
    $session = $value->getSession();
    if (!$session instanceof SessionNode) {
      $this->context->addViolation($constraint->wrongSessionBundleMessage);
      return;
    }
    $routine_id = $session->getRoutine()?->id();
    $paragraph_parent = $exercise->getParentEntity();
    if (!$routine_id || !$paragraph_parent instanceof NodeInterface || $paragraph_parent->id() !== $routine_id) {
      $this->context->addViolation($constraint->message);
    }
  }

}
