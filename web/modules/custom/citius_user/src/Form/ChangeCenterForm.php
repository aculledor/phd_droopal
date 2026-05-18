<?php

declare(strict_types=1);

namespace Drupal\citius_user\Form;

use Drupal\citius_user\CurrentCenterResolver;
use Drupal\citius_user\UserFields;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to change center.
 */
class ChangeCenterForm extends FormBase {

  /**
   * Current center resolver.
   *
   * @var \Drupal\citius_user\CurrentCenterResolver
   */
  protected CurrentCenterResolver $currentCenterResolver;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container):static {
    $instance = parent::create($container);
    $instance->currentCenterResolver = $container->get(CurrentCenterResolver::class);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'citius_user_change_center';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $user_id = $this->currentUser->id();
    if (!$user_id) {
      return $form;
    }
    $user = $this->entityTypeManager->getStorage('user')->load($user_id);
    if (!$user instanceof UserInterface) {
      return $form;
    }

    $centers = $user->get(UserFields::CENTER)->referencedEntities();
    $options = [];
    foreach ($centers as $center) {
      $options[$center->id()] = $center->label();
    }

    $form['center'] = [
      '#type' => 'select',
      '#title' => $this->t('Center'),
      '#title_display' => 'invisible',
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => $this->currentCenterResolver->get(),
      '#ajax' => [
        'callback' => '::changeCenter',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback to change center.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function changeCenter(array &$form, FormStateInterface $form_state): AjaxResponse {
    $this->currentCenterResolver->set((int) $form_state->getValue('center'));
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand(Url::fromRoute('<current>')->toString()));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
