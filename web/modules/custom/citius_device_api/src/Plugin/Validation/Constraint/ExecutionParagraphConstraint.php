<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validate that execution paragraphs belong to the specified session.
 */
#[Constraint(
  id: 'ExecutionParagraph',
  label: new TranslatableMarkup('Exercise paragraphs', options: ['context' => 'Validation'])
)]
final class ExecutionParagraphConstraint extends SymfonyConstraint {

  /**
   * Message when the paragraph does not belong to the specified session.
   *
   * @var string
   */
  public string $message = 'Paragraph does not belong to specified session.';

  /**
   * The message displayed when the paragraph field is left empty.
   *
   * @var string
   */
  public string $emptyMessage = 'Paragraph field cannot be empty.';

  /**
   * The message displayed when the paragraph bundle is not valid.
   *
   * @var string
   */
  public string $wrongBundleMessage = 'Paragraph bundle is not valid for this field.';

  /**
   * The message displayed when the session bundle is not valid.
   *
   * @var string
   */
  public string $wrongSessionBundleMessage = 'Session bundle is not valid for this session.';

}
