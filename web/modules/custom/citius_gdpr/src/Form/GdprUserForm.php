<?php

declare(strict_types=1);

namespace Drupal\citius_gdpr\Form;

use Drupal\citius_gdpr\GdprService;
use Drupal\citius_gdpr\GdprUserFields;
use Drupal\citius_user\UserRoles;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to anonymize and deanonymize users.
 */
class GdprUserForm extends FormBase {

  /**
   * GDPR service.
   *
   * @var \Drupal\citius_gdpr\GdprService
   */
  protected GdprService $gdprService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->gdprService = $container->get(GdprService::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'citius_gdpr_gdpr_user';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL): array {
    if (!$user) {
      throw new NotFoundHttpException();
    }
    $route_name = $this->getRouteMatch()->getRouteName();
    $action = $route_name === 'citius_gdpr.anonymize_user' ? 'anonymize' : 'deanonymize';
    $form_state->set('action', $action);
    $form_state->set('user', $user);
    $form['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $action === 'anonymize' ? $this->t('Are you sure you want to anonymize this user?') : $this->t('Are you sure you want to deanonymize this user?'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $action === 'anonymize' ? $this->t('Anonymize') : $this->t('Deanonymize'),
      ],
    ];

    return $form;
  }

  /**
   * Check access to an anonymization form.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user.
   * @param \Drupal\user\UserInterface|null $user
   *   User entity.
   */
  public function checkAnonymizeAccess(AccountProxyInterface $account, ?UserInterface $user): AccessResultInterface {
    if (!$account->hasPermission('create patient user')) {
      return AccessResult::forbidden();
    }
    if (!$user || !$user->hasRole(UserRoles::PATIENT)) {
      return AccessResult::forbidden();
    }
    $gdpr_date = $user->get(GdprUserFields::DATE);
    if (!$gdpr_date->isEmpty()) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

  /**
   * Check access to a deanonymization form.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user.
   * @param \Drupal\user\UserInterface|null $user
   *   User entity.
   */
  public function checkDeanonymizeAccess(AccountProxyInterface $account, ?UserInterface $user): AccessResultInterface {
    if (!$account->hasPermission('create patient user')) {
      return AccessResult::forbidden();
    }
    if (!$user || !$user->hasRole(UserRoles::PATIENT)) {
      return AccessResult::forbidden();
    }
    $gdpr_date = $user->get(GdprUserFields::DATE);
    if ($gdpr_date->isEmpty() || $user->get(GdprUserFields::NAME)->isEmpty() || $user->get(GdprUserFields::SURNAME)->isEmpty()) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $action = $form_state->get('action');
    /** @var \Drupal\user\UserInterface $user */
    $user = $form_state->get('user');
    if ($action === 'anonymize') {
      $this->gdprService->scheduleAnonymization($user);
      $this->messenger()->addStatus($this->t('The user has been scheduled for anonymization.'));
    }
    else {
      $this->gdprService->deanonymize($user);
      $this->messenger()->addStatus($this->t('The user has been deanonymized.'));
    }
    $form_state->setRedirect('entity.user.canonical', ['user' => $user->id()]);
  }

}
