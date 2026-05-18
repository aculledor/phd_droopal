<?php

namespace Drupal\citius_content\Event;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\Component\EventDispatcher\Event;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Gather all extra data for an exercise.
 */
class ExerciseExtraDataEvent extends Event {

  public const string NAME = 'citius_content.exercise_extra_data';

  /**
   * Data related to exercise.
   *
   * @var array
   */
  protected array $data = [];

  public function __construct(
    protected ParagraphInterface $exercise,
    protected SessionNode $session,
  ) {}

  /**
   * Get extra data.
   *
   * @return array
   *   Extra data.
   */
  public function getData(): array {
    return $this->data;
  }

  /**
   * Add extra data.
   *
   * @param array $data
   *   Extra data.
   */
  public function addData(array $data): void {
    $this->data = array_merge($this->data, $data);
  }

  /**
   * Get exercise.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   Exercise paragraph.
   */
  public function getExercise(): ParagraphInterface {
    return $this->exercise;
  }

  /**
   * Get session associated with exercise.
   *
   * @return \Drupal\citius_content\Entity\SessionNode
   *   Session node.
   */
  public function getSession(): SessionNode {
    return $this->session;
  }

}
