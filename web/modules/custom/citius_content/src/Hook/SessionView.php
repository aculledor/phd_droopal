<?php

namespace Drupal\citius_content\Hook;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\citius_content\Event\ExerciseExtraDataEvent;
use Drupal\citius_content\NodeFields;
use Drupal\citius_content\ParagraphFields;
use Drupal\citius_content\SessionState;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Renders session.
 */
class SessionView {

  use StringTranslationTrait;

  public function __construct(
    protected EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Implements hook_entity_view_alter().
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    if (!($entity instanceof SessionNode) || $display->getOriginalMode() !== 'full') {
      return;
    }

    unset($build['#contextual_links']);

    $fields = [];
    foreach (Element::children($build) as $field) {
      if (str_starts_with($field, 'field_')) {
        $fields[$field] = $build[$field];
      }
      unset($build[$field]);
    }

    if (isset($fields[NodeFields::GLASSES]) && !$entity->get(NodeFields::GLASSES)->isEmpty()) {
      $glass = $entity->get(NodeFields::GLASSES)->entity;
      $glass_device_id = $this->getDeviceExternalId($glass);

      if ($glass_device_id) {
        $glass_field = $fields[NodeFields::GLASSES];

        $fields[NodeFields::GLASSES] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['session__device-field-with-status'],
          ],
          'field' => $glass_field,
          'status_dot' => $this->renderDeviceStatusDot($glass_device_id),
        ];
      }
    }

    if ($entity->hasField(NodeFields::CAMERA) && isset($fields[NodeFields::CAMERA]) && !$entity->get(NodeFields::CAMERA)->isEmpty()) {
      $camera = $entity->get(NodeFields::CAMERA)->entity;
      $camera_device_id = $this->getDeviceExternalId($camera);

      if ($camera_device_id) {
        $camera_field = $fields[NodeFields::CAMERA];

        $fields[NodeFields::CAMERA] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['session__device-field-with-status'],
          ],
          'field' => $camera_field,
          'status_dot' => $this->renderDeviceStatusDot($camera_device_id),
        ];
      }
    }

    $build['card'] = [
      '#type' => 'component',
      '#component' => 'citius:collapsible-card',
      '#slots' => [
      'header' => [
        'fields' => [
          ...$fields,
          '#type' => 'container',
          '#attributes' => [
            'class' => ['session__content'],
          ],
        ],
      ],
        'header_right' => $this->renderButtons(),
        'content' => [
          'table' => $this->renderExercisesTable($entity),
        ],
      ],
      '#props' => [
        'title' => $entity->label(),
        'uncollapsible' => TRUE,
        'classes' => ['session-tracker'],
      ],
      '#attached' => [
        'library' => [
          'citius_content/drupal.session_tracker',
          'citius_content/drupal.device_status_dots',
        ],
      ],
    ];
  }

  /**
   * Render session exercises as table.
   *
   * @param \Drupal\citius_content\Entity\SessionNode $session
   *   Session node.
   *
   * @return array
   *   Render array.
   */
  protected function renderExercisesTable(SessionNode $session): array {
    $routine = $session->getRoutine();
    $session_state = $session->getSessionState();
    $table = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['session__exercises'],
        'data-session' => $session->id(),
        'data-state' => $session->getSessionState()->value,
      ],
      '#empty' => $this->t('No exercises'),
      '#header' => [
        $this->t('Exercise'),
        $this->t('Duration'),
        $this->t('Intensity'),
        $this->t('Expected results'),
        $this->t('Results obtained'),
        $this->t('Status'),
      ],
    ];
    $exercises = $routine?->get(NodeFields::EXERCISES)->referencedEntities() ?? [];
    $rows = [];
    $settings = [];
    /** @var \Drupal\paragraphs\ParagraphInterface $exercise */
    foreach ($exercises as $exercise) {
      $duration = (int) ($exercise->get(ParagraphFields::DURATION)->value ?? 0);
      $intensity = (int) ($exercise->get(ParagraphFields::INTENSITY)->value ?? 1);
      $expected_results = $intensity !== 0 ? $duration / $intensity : 0;
      $settings[$exercise->id()] = [
        'duration' => $duration,
        'expectedResults' => (int) $expected_results,
      ];
      $event = new ExerciseExtraDataEvent($exercise, $session);
      $this->eventDispatcher->dispatch($event, ExerciseExtraDataEvent::NAME);
      $data = $event->getData();
      $results = $data['results'] ?? [];
      $row = [
        $exercise->get(ParagraphFields::EXERCISE)->entity->label(),
        [
          'data' => $exercise->get(ParagraphFields::DURATION)->view([
            'label' => 'hidden',
          ]),
        ],
        $intensity,
        (int) $expected_results,
        ['data' => $this->renderResultsColumn($results)],
        ['data' => $this->renderExerciseStatus((int) $expected_results, $results, $session_state)],
      ];
      $rows[] = [
        'data' => $row,
        'data-exercise' => $exercise->id(),
        'data-expected-results' => (int) $expected_results,
        'class' => 'session__exercise',
      ];
      if (!empty($results)) {
        $rows[] = [
          'data' => [
            [
              'data' => $this->renderExerciseResults($results),
              'colspan' => 6,
            ],
          ],
          'class' => 'session__exercise-results',
          'data-exercise' => $exercise->id(),
        ];
      }
    }
    $table['#rows'] = $rows;
    $table['#attached']['drupalSettings']['sessionTracker']['session_' . $session->id()] = [
      'session' => $session->id(),
      'exercises' => $settings,
    ];
    return $table;
  }

  /**
   * Render exercise status.
   *
   * @param int $expected_results
   *   Expected results count.
   * @param array $results
   *   Results.
   * @param \Drupal\citius_content\SessionState $session_state
   *   Session state.
   *
   * @return array
   *   Render array.
   */
  protected function renderExerciseStatus(int $expected_results, array $results, SessionState $session_state): array {
    $count = count($results);
    if ($session_state === SessionState::Scheduled) {
      $class = '';
    }
    elseif ($count !== $expected_results) {
      if ($session_state === SessionState::Finished) {
        $class = 'failure';
      }
      else {
        $class = '';
      }
    }
    else {
      $class = count(array_filter(array_column($results, 'result'), static fn($item) => $item === 'success')) === $count ? 'success' : 'failure';
    }
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['session__exercise-status', $class],
        'title' => $class,
        'aria-label' => $class,
      ],
    ];
  }

  /**
   * Render results column.
   *
   * @param array $results
   *   Results.
   *
   * @return array
   *   Render array.
   */
  public function renderResultsColumn(array $results): array {
    if (empty($results)) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['session__results-column'],
          'data-results-count' => 0,
        ],
        '#value' => '-',
      ];
    }
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['session__results-column'],
        'data-results-count' => count($results),
      ],
      'count' => ['#markup' => count($results)],
      'button' => [
        '#type' => 'button',
        '#value' => $this->t('Show'),
        '#attributes' => [
          'class' => ['button', 'button--only-icon', 'icon-eye'],
        ],
      ],
    ];
  }

  /**
   * Render results of exercise.
   *
   * @param array $results
   *   Results.
   *
   * @return array
   *   Render array of results.
   */
  public function renderExerciseResults(array $results): array {
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Repetition'),
        $this->t('Head <span>(X, Z, Y Value)</span>'),
        $this->t('Left arm <span>(X, Z, Y Value)</span>'),
        $this->t('Right arm <span>(X, Z, Y Value)</span>'),
        $this->t('Result'),
      ],
    ];
    $rows = [];
    $i = 1;
    foreach ($results as $result) {
      $rows[] = [
        $i,
        implode(', ', $result['coordinates']['head'] ?? []),
        implode(', ', $result['coordinates']['left'] ?? []),
        implode(', ', $result['coordinates']['right'] ?? []),
        $result['result_label'] ?? '',
      ];
      $i++;
    }
    $table['#rows'] = $rows;
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['session__exercise-results-table'],
      ],
      'wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['session__exercise-results-wrapper'],
        ],
        'table' => $table,
      ],
    ];
  }

  /**
   * Renders buttons.
   *
   * @return array
   *   Render array.
   */
  protected function renderButtons(): array {
    $actions = [
      'start' => $this->t('Start'),
      'calibrate_standing_height' => $this->t('Measure and save user height'),
      'calibrate_squat_height' => $this->t('Measure and save squat height'),
      'pause' => $this->t('Pause'),
      'restart' => $this->t('Restart'),
      'stop' => $this->t('Stop'),
      'finish' => $this->t('Finish'),
    ];
    $buttons = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['session__buttons'],
      ],
      'error_placeholder' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['session__error-placeholder'],
        ],
      ],
    ];
    foreach ($actions as $action => $label) {
      $buttons[] = [
        '#type' => 'button',
        '#value' => $label,
        '#attributes' => [
          'class' => ['button', 'button--only-icon', 'icon-' . $action],
          'title' => $action,
          'data-action' => $action,
        ],
      ];
    }
    return $buttons;
  }

  protected function getDeviceExternalId(?EntityInterface $device): ?string {
    if (!$device || !$device->hasField(NodeFields::CODE) || $device->get(NodeFields::CODE)->isEmpty()) {
      return NULL;
    }

    return (string) $device->get(NodeFields::CODE)->value;
  }

  protected function renderDeviceStatusDot(?string $device_id): array {
    if (!$device_id) {
      return [];
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => [
          'device-status-dot',
          'device-status-dot--offline',
        ],
        'data-device-status-id' => $device_id,
        'title' => $this->t('Offline'),
        'aria-label' => $this->t('Offline'),
      ],
    ];
  }

}
