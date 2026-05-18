<?php

namespace Drupal\citius_content\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueBundleField constraint.
 */
final class UniqueBundleFieldConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof UniqueBundleFieldConstraint || !$value instanceof FieldItemListInterface) {
      return;
    }
    $entity = $value->getEntity();
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    $bundle = $constraint->getBundle();
    $field = $constraint->getField();
    if ($entity->bundle() !== $bundle || !$entity->hasField($field)) {
      return;
    }
    $field_value = $entity->get($field)->value;
    $bundle_key = $entity->getEntityType()->getKey('bundle');
    if (!$bundle_key || empty($field_value)) {
      return;
    }
    $query = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->getQuery()
      ->accessCheck(FALSE)
      ->condition($field, $field_value);
    if (!empty($bundle)) {
      $query->condition($bundle_key, $bundle);
    }
    $ids = $query->execute();
    if (!empty($ids)) {
      $id = reset($ids);
      if ($id !== $entity->id()) {
        $entity_label = $entity->getEntityType()->getSingularLabel();
        $field_label = $value->getFieldDefinition()->getLabel();
        $this->context->buildViolation($constraint->message)
          ->setParameter('@entity_type', $entity_label)
          ->setParameter('@field_name', (string) $field_label)
          ->setParameter('%value', (string) $field_value)
          ->addViolation();
      }
    }
  }

}
