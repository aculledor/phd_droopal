<?php

namespace Drupal\citius_content\Entity;

use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\NodeFields;
use Drupal\citius_content\SessionState;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Bundle class for Session nodes.
 */
class SessionNode extends Node {

  /**
   * Get session routine.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Routine node.
   */
  public function getRoutine(): ?NodeInterface {
    /** @var \Drupal\node\NodeInterface|null $routine */
    $routine = $this->get(NodeFields::ROUTINE)->entity;
    return $routine;
  }

  /**
   * Get session state.
   *
   * @return \Drupal\citius_content\SessionState
   *   Session state enum.
   */
  public function getSessionState(): SessionState {
    return SessionState::from($this->get(NodeFields::SESSION_STATE)->value);
  }

  /**
   * Set session state.
   *
   * @param \Drupal\citius_content\SessionState $state
   *   State enum.
   *
   * @return $this
   *   Session node.
   */
  public function setSessionState(SessionState $state): SessionNode {
    $this->set(NodeFields::SESSION_STATE, $state->value);
    return $this;
  }

  /**
   * Get exercises paragraphs.
   *
   * @return \Drupal\paragraphs\ParagraphInterface[]
   *   Set of exercise paragraph entity.
   */
  public function getExercises(): array {
    return $this->getRoutine()?->get(NodeFields::EXERCISES)->referencedEntities() ?? [];
  }

  /**
   * Get glasses endpoint URL.
   *
   * @return string|null
   *   Glasses endpoint.
   */
  public function getGlassEndpoint(): ?string {
    $glasses = $this->get(NodeFields::GLASSES)->entity;
    if ($glasses instanceof NodeInterface && $glasses->bundle() === NodeBundles::DEVICE) {
      /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem|null $item */
      $item = $glasses->get(NodeFields::ENDPOINT)->first();
      return $item?->uri;
    }
    return NULL;
  }

  /**
   * Get glasses device ID.
   *
   * @return string|null
   *   Glasses device ID.
   */
  public function getGlassDeviceId(): ?string {
    $glasses = $this->get(NodeFields::GLASSES)->entity;
    if ($glasses instanceof NodeInterface && $glasses->bundle() === NodeBundles::DEVICE) {
      $value = $glasses->get(NodeFields::CODE)->value;
      return $value ? (string) $value : NULL;
    }
    return NULL;
  }

}
