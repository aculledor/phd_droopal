<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\EventSubscriber;

use Drupal\citius_content\Event\ExerciseExtraDataEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add extra data to exercise.
 */
final class ExerciseSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Adds extra data to exercise.
   *
   * @param \Drupal\citius_content\Event\ExerciseExtraDataEvent $event
   *   Event object.
   */
  public function onExerciseExtraData(ExerciseExtraDataEvent $event): void {
    $executions = $this->entityTypeManager
      ->getStorage('execution')
      ->loadByProperties([
        'exercise' => $event->getExercise()->id(),
        'session' => $event->getSession()->id(),
      ]);
    $data = [];
    /** @var \Drupal\citius_device_api\Entity\ExecutionInterface $execution */
    foreach ($executions as $execution) {
      $data[] = [
        'date' => $execution->getExecutionDate()?->format('Y-m-d\TH:i:s'),
        'result_label' => $execution->getResultLabel(),
        'result' => $execution->getResult(),
        'coordinates' => $execution->getCoordinates(),
      ];
    }
    $event->addData(['results' => $data]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ExerciseExtraDataEvent::NAME => ['onExerciseExtraData'],
    ];
  }

}
