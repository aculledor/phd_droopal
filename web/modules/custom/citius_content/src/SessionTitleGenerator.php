<?php

declare(strict_types=1);

namespace Drupal\citius_content;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\node\NodeInterface;

/**
 * Generates a title for a session content type.
 */
final class SessionTitleGenerator {

  /**
   * Temporary title for session nodes, when ID is not known.
   */
  private const TEMP_TITLE = 'SESSION_TEMP_TITLE';

  public function __construct(
    private readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * Constructs a session title.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The session node from which the title is generated.
   *
   * @return string
   *   The generated session title.
   */
  public function getSessionTitle(NodeInterface $node): string {
    if ($node->isNew()) {
      return self::TEMP_TITLE;
    }
    $current_title = (string) $node->label();
    if ($current_title !== self::TEMP_TITLE) {
      return $current_title;
    }
    $date = $this->dateFormatter->format($node->getCreatedTime(), 'custom', 'Ymd');
    /** @var \Drupal\user\UserInterface|null $patient */
    $patient = $node->get(NodeFields::PATIENT)->entity;
    $name = $patient?->getDisplayName() ?? '';
    $name = str_replace(' ', '_', mb_strtoupper((string) $name));
    return $node->id() . '_' . $name . '_' . $date;
  }

}
