<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an interface defining an execution entity type.
 */
interface ExecutionInterface extends ContentEntityInterface {

  /**
   * Gets the session.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The session entity or null if not set.
   */
  public function getSession(): ?EntityInterface;

  /**
   * Sets the session.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $session
   *   The session entity.
   *
   * @return $this
   */
  public function setSession(?EntityInterface $session): ExecutionInterface;

  /**
   * Gets the exercise.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The exercise entity or null if not set.
   */
  public function getExercise(): ?EntityInterface;

  /**
   * Sets the exercise.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $exercise
   *   The exercise entity.
   *
   * @return $this
   */
  public function setExercise(?EntityInterface $exercise): ExecutionInterface;

  /**
   * Gets the result.
   *
   * @return string|null
   *   The result value (one of ExecutionResult enum values) or null if not set.
   */
  public function getResult(): ?string;

  /**
   * Gets the result label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The result label.
   */
  public function getResultLabel(): TranslatableMarkup|string;

  /**
   * Sets the result.
   *
   * @param string|null $result
   *   The result value (one of ExecutionResult enum values).
   *
   * @return $this
   */
  public function setResult(?string $result): ExecutionInterface;

  /**
   * Gets the execution date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The execution date or null if not set.
   */
  public function getExecutionDate(): ?DrupalDateTime;

  /**
   * Sets the execution date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $execution_date
   *   The execution date.
   *
   * @return $this
   */
  public function setExecutionDate(?DrupalDateTime $execution_date): ExecutionInterface;

  /**
   * Gets the JSON data as decoded array.
   *
   * @return array|null
   *   The decoded JSON data or null if not set.
   */
  public function getJsonData(): ?array;

  /**
   * Sets the JSON data.
   *
   * @param array|null $json_data
   *   The JSON data as array.
   *
   * @return $this
   */
  public function setJsonData(?array $json_data): ExecutionInterface;

  /**
   * Gets all coordinates structured by direction.
   *
   * @return array
   *   Array with keys head, left, right, each containing x, y, z coordinates.
   */
  public function getCoordinates(): array;

  /**
   * Sets all coordinates from structured array.
   *
   * @param array $coordinates
   *   Array with keys head, left, right, each containing x, y, z coordinates.
   *
   * @return $this
   */
  public function setCoordinates(array $coordinates): ExecutionInterface;

}
