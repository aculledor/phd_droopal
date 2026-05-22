<?php

declare(strict_types=1);

namespace Drupal\citius_analytics\Controller;

use Drupal\citius_analytics\FilterOptionsRepository;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ProcessMiningController extends ControllerBase {
  public function __construct(
    protected FilterOptionsRepository $filterOptionsRepository,
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManagerService,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get(FilterOptionsRepository::class),
      $container->get('database'),
      $container->get('entity_type.manager'),
    );
  }

  public function page(Request $request): array {
    $filters = $this->getFilters($request);
    $patterns = $this->buildPatterns($filters);
    $rows = [];
    foreach ($patterns as $pattern) {
      $rows[] = ['data' => [(string) $pattern['length'], implode(' → ', $pattern['items']), (string) $pattern['count'], (string) count($pattern['sessions'])]];
    }

    return [
      'filters' => $this->buildFilterForm($filters),
      'result' => [
        '#type' => 'table',
        '#header' => [$this->t('Longitud'), $this->t('Subtraza'), $this->t('Frecuencia absoluta'), $this->t('Sesiones únicas')],
        '#rows' => $rows,
        '#empty' => $this->t('No se encontraron subtrazas con los filtros seleccionados.'),
      ],
    ];
  }

  private function getFilters(Request $request): array {
    return [
      'patient' => (string) $request->query->get('patient', ''),
      'date_start' => (string) $request->query->get('date_start', ''),
      'date_end' => (string) $request->query->get('date_end', ''),
      'subtrace_length' => (string) $request->query->get('subtrace_length', ''),
      'intensity' => (string) $request->query->get('intensity', ''),
    ];
  }

  private function buildFilterForm(array $filters): array {
    $length_options = array_combine(range(3, 10), range(3, 10));
    $empty_option = $this->t('- Seleccionar -');

    return [
      '#type' => 'container',
      '#prefix' => '<form method="get">',
      '#suffix' => '</form>',
      '#attributes' => ['class' => ['citius-process-mining-form']],
      '#attached' => [
        'library' => ['citius_analytics/process_mining'],
      ],
      'row_1' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['form-row']],
        'patient' => ['#type' => 'select', '#name' => 'patient', '#title' => $this->t('Paciente'), '#options' => $this->filterOptionsRepository->getPatientOptions(), '#empty_option' => $empty_option, '#default_value' => $filters['patient'] !== '' ? $filters['patient'] : NULL],
        'subtrace_length' => ['#type' => 'select', '#name' => 'subtrace_length', '#title' => $this->t('Número de elementos en la subtraza'), '#options' => $length_options, '#empty_option' => $empty_option, '#default_value' => $filters['subtrace_length'] !== '' ? $filters['subtrace_length'] : NULL],
        'intensity' => ['#type' => 'select', '#name' => 'intensity', '#title' => $this->t('Intensidad'), '#options' => $this->filterOptionsRepository->getIntensityOptions(), '#empty_option' => $empty_option, '#default_value' => $filters['intensity'] !== '' ? $filters['intensity'] : NULL],
      ],
      'row_2' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['form-row', 'form-row--dates']],
        'date_start' => ['#type' => 'date', '#name' => 'date_start', '#title' => $this->t('Fecha desde'), '#default_value' => $filters['date_start']],
        'date_end' => ['#type' => 'date', '#name' => 'date_end', '#title' => $this->t('Fecha hasta'), '#default_value' => $filters['date_end']],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Buscar'),
            '#attributes' => ['class' => ['button', 'button--primary'], 'type' => 'submit', 'name' => 'op'],
          ],
        ],
      ],
    ];
  }

  private function buildPatterns(array $filters): array {
    $query = $this->database->select('execution', 'e');
    $query->fields('e', ['id', 'session', 'execution_date', 'exercise', 'result']);
    if ($filters['patient'] !== '') {
      $query->join('node__field_patient', 'nfp', 'nfp.entity_id = e.session');
      $query->condition('nfp.field_patient_target_id', (int) $filters['patient']);
    }
    if ($filters['intensity'] !== '') {
      $query->join('paragraph__field_intensity', 'pfi', 'pfi.entity_id = e.exercise');
      $query->condition('pfi.field_intensity_value', $filters['intensity']);
    }
    if ($filters['date_start'] !== '') {
      $query->condition('e.execution_date', $filters['date_start'] . ' 00:00:00', '>=');
    }
    if ($filters['date_end'] !== '') {
      $query->condition('e.execution_date', $filters['date_end'] . ' 23:59:59', '<=');
    }
    $query->orderBy('e.session', 'ASC');
    $query->orderBy('e.execution_date', 'ASC');
    $query->orderBy('e.id', 'ASC');

    $rows = $query->execute()->fetchAll();
    $exercise_labels = $this->loadExerciseLabels($rows);
    $traces = [];
    foreach ($rows as $row) {
      $session_id = (int) $row->session;
      $exercise_id = (int) $row->exercise;
      $exercise_label = $exercise_labels[$exercise_id] ?? ('EXERCISE_' . $exercise_id);
      $traces[$session_id][] = $exercise_label . '-' . strtoupper((string) $row->result);
    }

    $lengths = $filters['subtrace_length'] !== '' ? [(int) $filters['subtrace_length']] : range(3, 10);
    $patterns = [];
    foreach ($traces as $session_id => $trace) {
      foreach ($lengths as $length) {
        if (count($trace) < $length) {
          continue;
        }
        for ($i = 0; $i <= count($trace) - $length; $i++) {
          $subtrace = array_slice($trace, $i, $length);
          $key = implode('||', $subtrace);
          if (!isset($patterns[$key])) {
            $patterns[$key] = ['length' => $length, 'items' => $subtrace, 'count' => 0, 'sessions' => []];
          }
          $patterns[$key]['count']++;
          $patterns[$key]['sessions'][$session_id] = TRUE;
        }
      }
    }
    $patterns = array_values($patterns);
    usort($patterns, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
    return array_slice($patterns, 0, 10);
  }

  private function loadExerciseLabels(array $rows): array {
    $ids = [];
    foreach ($rows as $row) {
      $ids[(int) $row->exercise] = (int) $row->exercise;
    }
    if ($ids === []) {
      return [];
    }
    $entities = $this->entityTypeManagerService->getStorage('paragraph')->loadMultiple(array_values($ids));
    $labels = [];
    foreach ($entities as $entity) {
      $labels[(int) $entity->id()] = strtoupper((string) preg_replace('/\s+/', '_', (string) $entity->label()));
    }
    return $labels;
  }
}
