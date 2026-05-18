<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Entity\Handler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the execution entity type.
 */
final class ExecutionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['session'] = $this->t('Session');
    $header['execution_date'] = $this->t('Execution date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\citius_device_api\Entity\ExecutionInterface $entity */
    $row['id'] = $entity->id();
    $row['session'] = $entity->label();
    $row['execution_date'] = $entity->getExecutionDate()?->format('j M Y - H:i');
    return $row + parent::buildRow($entity);
  }

}
