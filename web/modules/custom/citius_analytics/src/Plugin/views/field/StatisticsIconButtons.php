<?php

namespace Drupal\citius_analytics\Plugin\views\field;

use Drupal\citius_user\UserRoles;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;

/**
 * Renders statistics links as icon buttons.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("statistics_icon_buttons")]
class StatisticsIconButtons extends EntityOperations {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): array|string {
    $entity = $this->getEntity($values);
    // Allow for the case where there is no entity, if we are on a non-required
    // relationship.
    if (!$entity instanceof UserInterface) {
      return '';
    }

    $entity = $this->getEntityTranslationByRelationship($entity, $values);
    $links = [];

    if ($entity instanceof UserInterface && $entity->hasRole(UserRoles::PATIENT)) {
      $operation = 'statistics';
      $links[$operation] = [
        '#type' => 'component',
        '#component' => 'citius:icon-button-link',
        '#props' => [
          'title' => $this->t('Statistics'),
          'icon' => 'chart-bar',
          'url' => Url::fromRoute('view.performance_chart.performance_chart', ['user' => $entity->id()])->toString(),
        ],
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['icon-button-links-wrapper']],
      'items' => $links,
    ];
  }

}
