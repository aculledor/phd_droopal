<?php

namespace Drupal\citius_device_api\Hook;

use Drupal\citius_content\NodeFields;
use Drupal\citius_content\TaxonomyFields;
use Drupal\citius_device_api\DeviceIdManager;
use Drupal\citius_device_api\Exception\DeviceIdException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Device node form alter.
 */
class DeviceNodeFormAlter {

  use StringTranslationTrait;

  public function __construct(
    protected DeviceIdManager $deviceIdManager,
    protected RequestStack $requestStack,
    protected MessengerInterface $messenger,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_node_device_form_alter')]
  public function deviceNodeFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $request = $this->requestStack->getCurrentRequest();
    if ($request?->getMethod() === 'GET') {
      if ($this->deviceIdManager->isActivated()) {
        $this->messenger->addError($this->t('Other device is being created. Close other tabs with this form to create a new device. If it does not work, wait a few minutes and try again.'));
        $form['#disabled'] = TRUE;
        return;
      }
      $this->deviceIdManager->activate();
    }
    $form[NodeFields::CODE]['#disabled'] = TRUE;
    $form[NodeFields::CODE]['widget'][0]['value']['#required'] = FALSE;
    $form['#validate'][] = [$this, 'validateForm'];
    $form['actions']['submit']['#submit'][] = [$this, 'submitForm'];
  }

  /**
   * Validation callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $model = $form_state->getValue([NodeFields::MODEL, 0, 'target_id']);
    if (empty($model)) {
      return;
    }
    /** @var \Drupal\taxonomy\TermInterface|null $model */
    $model = $this->entityTypeManager->getStorage('taxonomy_term')->load($model);
    /** @var \Drupal\taxonomy\TermInterface|null $model_type */
    $model_type = $model?->get(TaxonomyFields::TYPE)->entity;
    $type = $model_type?->get(TaxonomyFields::CODE)->value;
    if ($type !== 'glass') {
      return;
    }
    try {
      $form_state->set('device_id', $this->deviceIdManager->getDeviceId());
      $form_state->set('device_secret', $this->deviceIdManager->getDeviceSecret());
      $form_state->set('device_endpoint', $this->deviceIdManager->getDeviceEndpoint());
    }
    catch (DeviceIdException $e) {
      $form_state->setErrorByName('device_id', $this->translateError($e->getCode()) ?? $e->getMessage());
    }
  }

  /**
   * Translates an error code.
   *
   * @param int $code
   *   Error code.
   *
   * @return null|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translated error.
   */
  protected function translateError(int $code): ?TranslatableMarkup {
    return match ($code) {
      DeviceIdException::MANAGER_NOT_ACTIVE => $this->t('Device ID manager is not active.'),
      DeviceIdException::ID_ALREADY_CLAIMED => $this->t('Device ID is already claimed.'),
      DeviceIdException::ID_NOT_CLAIMED => $this->t('Device ID is not claimed yet.'),
      DeviceIdException::MANAGER_ALREADY_ACTIVE => $this->t('Device ID manager is already active.'),
      default => NULL,
    };
  }

  /**
   * Submit callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\node\NodeForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_object->getEntity();
    $node->set(NodeFields::CODE, $form_state->get('device_id'));
    $node->set(NodeFields::SECRET, $form_state->get('device_secret'));
    $node->set(NodeFields::ENDPOINT, $form_state->get('device_endpoint'));
    $node->save();
    $this->deviceIdManager->deactivate();
  }

}
