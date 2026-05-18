<?php

namespace Drupal\citius_content\Hook;

use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\SessionTitleGenerator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;

/**
 * Hooks related to session content types.
 */
readonly class SessionHooks {

  public function __construct(
    private SessionTitleGenerator $sessionTitleGenerator,
  ) {}

  /**
   * Alters session form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  #[Hook('form_node_session_form_alter')]
  #[Hook('form_node_session_edit_form_alter')]
  public function sessionFormAlter(array &$form, FormStateInterface $form_state): void {
    $form['title']['widget'][0]['value']['#required'] = FALSE;
    $form['title']['#access'] = FALSE;
  }

  /**
   * Set the session title on save.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   */
  #[Hook('entity_presave')]
  public function sessionPresave(EntityInterface $entity): void {
    if ($entity instanceof NodeInterface && $entity->bundle() === NodeBundles::SESSION) {
      $title = $this->sessionTitleGenerator->getSessionTitle($entity);
      $entity->setTitle($title);
    }
  }

  /**
   * Set the session title on insert.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   */
  #[Hook('entity_insert')]
  public function sessionInsert(EntityInterface $entity): void {
    if ($entity instanceof NodeInterface && $entity->bundle() === NodeBundles::SESSION) {
      $title = $this->sessionTitleGenerator->getSessionTitle($entity);
      $entity->setTitle($title);
      $entity->save();
    }
  }

}
