<?php

declare(strict_types=1);

namespace Drupal\citius_user\Form;

use Drupal\citius_user\CurrentCenterResolver;
use Drupal\citius_user\UserFields;
use Drupal\citius_user\UserRoles;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to select center.
 */
class SelectCenterForm extends FormBase {

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
  public static function create(ContainerInterface $container): static {
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
    return 'citius_user_select_center';
  }

  /**
   * Page title callback.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title.
   */
  public function title(): TranslatableMarkup {
    return $this->t(
      'Welcome @user, select the center for today',
      ['@user' => $this->currentUser()->getDisplayName()],
    );
  }

  /**
   * Access callback for route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Access result.
   */
  public function access(): AccessResultInterface {
    return AccessResult::allowedIf($this->currentUser->hasRole(UserRoles::PHYSIOTHERAPIST));
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
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => $this->currentCenterResolver->get(),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Follow'),
        '#attributes' => [
          'class' => ['button--icon', 'button--icon-arrow-right'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->currentCenterResolver->set((int) $form_state->getValue('center'));
    $form_state->setRedirect('<front>');
  }

}
