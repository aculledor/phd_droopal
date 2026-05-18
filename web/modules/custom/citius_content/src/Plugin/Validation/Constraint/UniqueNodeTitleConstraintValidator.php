<?php

namespace Drupal\citius_content\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueNodeTitle constraint.
 */
final class UniqueNodeTitleConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs the object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $entity, Constraint $constraint): void {
    if (!$constraint instanceof UniqueNodeTitleConstraint || !$entity instanceof NodeInterface) {
      return;
    }

    $bundles = $constraint->getBundles();
    if (!in_array($entity->bundle(), $bundles, TRUE)) {
      return;
    }
    $ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $entity->bundle())
      ->condition('title', $entity->label())
      ->execute();
    if (!empty($ids)) {
      $id = reset($ids);
      if ($id !== $entity->id()) {
        $this->context->buildViolation($constraint->message)
          ->addViolation();
      }
    }
  }

}
