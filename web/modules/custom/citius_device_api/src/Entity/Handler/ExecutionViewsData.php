<?php

namespace Drupal\citius_device_api\Entity\Handler;

use Drupal\views\EntityViewsData;

/**
 * Views data for execution entity.
 */
class ExecutionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    $data = parent::getViewsData();
    $data['execution']['execution_date']['filter']['id'] = 'datetime';
    $data['execution']['execution_date']['filter']['field_name'] = 'execution_date';
    return $data;
  }

}
