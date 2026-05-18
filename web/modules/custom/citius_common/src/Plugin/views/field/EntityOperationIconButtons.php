<?php

namespace Drupal\citius_common\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;

/**
 * Renders edit and delete operations as icon buttons.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("entity_operation_icon_buttons")]
class EntityOperationIconButtons extends EntityOperations {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): array|string {
    $entity = $this->getEntity($values);
    // Allow for the case where there is no entity, if we are on a non-required
    // relationship.
    if ($entity === NULL) {
      return '';
    }

    $entity = $this->getEntityTranslationByRelationship($entity, $values);
    $operations = [
      'update',
      'delete',
    ];
    $links = [];
    foreach ($operations as $operation) {
      if ($entity->access($operation)) {
        $link_template = $this->getLinkTemplate($entity, $operation);
        if ($link_template) {
          $url = $entity->toUrl($link_template);
          if ($this->options['destination']) {
            $url->setOption('query', $this->getDestinationArray());
          }
          $links[$operation] = [
            '#type' => 'component',
            '#component' => 'citius:icon-button-link',
            '#props' => [
              'title' => $this->getOperationTitle($operation),
              'icon' => $this->getIcon($operation),
              'url' => $url->toString(),
            ],
          ];
        }
      }
    }

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['icon-button-links-wrapper']],
      'items' => $links,
    ];
  }

  /**
   * Get link operation title.
   *
   * @param string $operation
   *   Operation.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Operation title.
   */
  protected function getOperationTitle(string $operation): TranslatableMarkup|string {
    return match ($operation) {
      'update' => $this->t('Edit'),
      'delete' => $this->t('Delete'),
      default => '',
    };
  }

  /**
   * Get link operation icon.
   *
   * @param string $operation
   *   Operation.
   *
   * @return string
   *   Icon name.
   */
  protected function getIcon(string $operation): string {
    return match ($operation) {
      'update' => 'edit',
      'delete' => 'delete',
      default => '',
    };
  }

  /**
   * Get link template for operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   * @param string $operation
   *   Operation.
   *
   * @return string|null
   *   Link template.
   */
  protected function getLinkTemplate(EntityInterface $entity, string $operation): ?string {
    if ($operation === 'update') {
      return 'edit-form';
    }
    if ($operation === 'delete') {
      if ($entity->hasLinkTemplate('delete-form')) {
        return 'delete-form';
      }
      if ($entity->hasLinkTemplate('cancel-form')) {
        return 'cancel-form';
      }
    }
    return NULL;
  }

}
