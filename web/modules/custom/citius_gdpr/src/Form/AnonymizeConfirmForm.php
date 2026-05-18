<?php

declare(strict_types=1);

namespace Drupal\citius_gdpr\Form;

use Drupal\citius_gdpr\GdprService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Confirmation form to anonymize users.
 */
final class AnonymizeConfirmForm extends ConfirmFormBase {

  /**
   * Private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $tempStore;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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
    $instance->tempStore = $container->get('tempstore.private')->get('citius_gdpr_anonymize_confirm');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->gdprService = $container->get(GdprService::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'citius_gdpr_anonymize_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to anonymize selected users?');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array|RedirectResponse {
    $form = parent::buildForm($form, $form_state);

    $ids = $this->tempStore->get('ids');

    if (empty($ids) || !is_array($ids)) {
      $this->messenger()->addError($this->t('No users selected for anonymization.'));
      return $this->redirect('view.users_list.patients');
    }

    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($ids);
    $user_names = [];
    foreach ($users as $user) {
      $user_names[] = $user->getDisplayName();
    }
    $form['users'] = [
      '#theme' => 'item_list',
      '#items' => $user_names,
      '#title' => $this->t('Users to be anonymized'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('view.users_list.patients');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $ids = $this->tempStore->get('ids');
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($ids);
    foreach ($users as $user) {
      $username = $user->getDisplayName();
      $this->gdprService->scheduleAnonymization($user);
      $this->messenger()->addStatus($this->t('The user @name has been scheduled for anonymization.', ['@name' => $username]));
    }
    $form_state->setRedirectUrl(new Url('view.users_list.patients'));
  }

}
