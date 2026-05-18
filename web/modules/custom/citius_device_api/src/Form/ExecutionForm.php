<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the execution entity edit forms.
 */
final class ExecutionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $groups = [
      'head' => $this->t('Head'),
      'left' => $this->t('Left controller'),
      'right' => $this->t('Right controller'),
    ];
    $coordinates = ['x', 'y', 'z'];
    foreach ($groups as $group => $label) {
      $form[$group] = [
        '#type' => 'details',
        '#title' => $label,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#tree' => FALSE,
        '#required' => TRUE,
      ];
      foreach ($coordinates as $coordinate) {
        $field_name = sprintf('%s_%s', $group, $coordinate);
        $form[$field_name]['#group'] = $group;
        $form[$field_name]['#title'] = strtoupper($coordinate);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New execution %label has been created.', $message_args));
        $this->logger('citius_device_api')->notice('New execution %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The execution %label has been updated.', $message_args));
        $this->logger('citius_device_api')->notice('The execution %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

}
