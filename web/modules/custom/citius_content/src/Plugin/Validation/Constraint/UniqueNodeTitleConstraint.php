<?php

namespace Drupal\citius_content\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Unique title between nodes of current type.
 */
#[Constraint(
  id: 'UniqueNodeTitle',
  label: new TranslatableMarkup('Unique node title', options: ['context' => 'Validation'])
)]
final class UniqueNodeTitleConstraint extends SymfonyConstraint {

  /**
   * Error message.
   *
   * @var string
   */
  public string $message = 'Node with this title already exists.';

  /**
   * Bundles to validate.
   *
   * @var array
   */
  protected array $bundles = [];

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'bundles';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['bundles'];
  }

  /**
   * Get a list of bundles.
   *
   * @return array
   *   List of bundle machine names.
   */
  public function getBundles(): array {
    return $this->bundles;
  }

}
