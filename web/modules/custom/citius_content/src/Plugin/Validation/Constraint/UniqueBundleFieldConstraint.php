<?php

namespace Drupal\citius_content\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Unique field across nodes of the current type.
 */
#[Constraint(
  id: 'UniqueBundleField',
  label: new TranslatableMarkup('Unique field across bundle', options: ['context' => 'Validation'])
)]
final class UniqueBundleFieldConstraint extends SymfonyConstraint {

  /**
   * Error message.
   *
   * @var string
   */
  public string $message = 'A @entity_type with @field_name %value already exists.';

  /**
   * Bundle to validate.
   *
   * @var string|null
   */
  protected ?string $bundle = NULL;

  /**
   * Field to validate.
   *
   * @var string
   */
  protected string $field;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'field';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['bundle', 'field'];
  }

  /**
   * Get bundle.
   *
   * @return string|null
   *   Bundle machine name.
   */
  public function getBundle(): ?string {
    return $this->bundle;
  }

  /**
   * Get field.
   *
   * @return string
   *   Field name.
   */
  public function getField(): string {
    return $this->field;
  }

}
