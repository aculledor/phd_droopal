<?php

namespace Drupal\citius_analytics\Hook;

use Drupal\citius_analytics\FilterOptionsRepository;
use Drupal\citius_content\Colors;
use Drupal\citius_device_api\Entity\Execution;
use Drupal\citius_device_api\ExecutionResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;

/**
 * Hooks related to charts.
 */
class ChartHooks {

  use StringTranslationTrait;

  /**
   * Static cache of result options.
   *
   * @var array|null
   */
  protected static ?array $reverseResultOptions = NULL;

  public function __construct(
    protected FilterOptionsRepository $filterOptionsRepository,
  ) {}

  /**
   * Implements hook_chart_CHART_ID_alter().
   */
  #[Hook('chart_executions_chart_execution_result_alter')]
  #[Hook('chart_executions_chart_user_execution_alter')]
  public function executionResultChartAlter(array &$element): void {
    $request = \Drupal::requestStack()->getCurrentRequest();
    $selected_chart = $request?->query->get('chart_type', 'totals') ?? 'totals';

    if ($selected_chart === 'evolution') {
      $this->buildExecutionEvolutionChart($element, $request);
      return;
    }

    foreach (Element::children($element) as $key) {
      $type = $element[$key]['#type'] ?? '';
      if ($type !== 'chart_data') {
        continue;
      }
      $colors = [];
      $sum = array_sum($element[$key]['#data']);
      $i = 0;
      foreach ($element[$key]['#mapped_data'] as $data_key => $value) {
        $percent = $sum ? round($value / $sum * 100) : 0;
        $colors[] = $this->getColor($data_key);
        $element['xaxis']['#labels'][$i] .= ' (' . $percent . '%)';
        $i++;
      }
      $element[$key]['#color'] = $colors;
    }

    $element['#raw_options']['options']['scales']['y']['ticks']['stepSize'] = 1;
    $element['#raw_options']['options']['plugins']['datalabels']['color'] = Colors::White->value;
  }

  /**
   * Build evolution chart data grouped by day and outcome.
   */
  protected function buildExecutionEvolutionChart(array &$element, ?Request $request): void {
    [$from, $to] = $this->resolveDateRange($request);

    $day_labels = [];
    $cursor = clone $from;
    while ($cursor <= $to) {
      $day_labels[] = $cursor->format('Y-m-d');
      $cursor = $cursor->modify('+1 day');
    }

    $series = [
      ExecutionResult::Failure->value => array_fill_keys($day_labels, 0),
      ExecutionResult::Missed->value => array_fill_keys($day_labels, 0),
      ExecutionResult::Success->value => array_fill_keys($day_labels, 0),
    ];

    $query = \Drupal::database()->select('execution', 'e');
    $query->addExpression('DATE(e.execution_date)', 'day');
    $query->addField('e', 'result', 'result');
    $query->addExpression('COUNT(*)', 'total');
    $query->condition('e.execution_date', $from->format('Y-m-d 00:00:00'), '>=');
    $query->condition('e.execution_date', $to->modify('+1 day')->format('Y-m-d 00:00:00'), '<');

    $routine = (int) ($request?->query->get('routine') ?? 0);
    if ($routine > 0) {
      $query->condition('e.session', $routine);
    }
    $exercise = (int) ($request?->query->get('exercise') ?? 0);
    if ($exercise > 0) {
      $query->condition('e.exercise', $exercise);
    }
    $patient = (int) ($request?->query->get('patient') ?? 0);
    if ($patient > 0) {
      $query->where("JSON_UNQUOTE(JSON_EXTRACT(e.json_data, '$.metadata.user_id')) = :patient", [
        ':patient' => (string) $patient,
      ]);
    }
    $intensity = $request?->query->get('intensity');
    if ($intensity !== NULL && $intensity !== '') {
      $query->join('paragraph__field_intensity', 'pfi', 'pfi.entity_id = e.exercise');
      $query->condition('pfi.field_intensity_value', (int) $intensity);
    }

    $query->groupBy('day');
    $query->groupBy('e.result');
    $query->orderBy('day', 'ASC');

    foreach ($query->execute()->fetchAll() as $row) {
      $day = (string) $row->day;
      $result = (string) $row->result;
      if (isset($series[$result][$day])) {
        $series[$result][$day] = (int) $row->total;
      }
    }

    foreach (Element::children($element) as $child) {
      unset($element[$child]);
    }

    $labels = [
      ExecutionResult::Failure->value => 'Fracaso',
      ExecutionResult::Missed->value => 'Fallo',
      ExecutionResult::Success->value => 'Éxito',
    ];

    foreach ($series as $result_key => $values_by_day) {
      $dataset_key = 'line_' . $result_key;
      $pairs = [];
      $index = 0;
      foreach ($values_by_day as $value) {
        $pairs[] = [$index, $value];
        $index++;
      }
      $element[$dataset_key] = [
        '#type' => 'chart_data',
        '#title' => $labels[$result_key],
        '#chart_type' => 'line',
        '#data' => $pairs,
        '#color' => $this->getColor($labels[$result_key]),
      ];
    }

    $element['xaxis']['#type'] = 'chart_xaxis';
    $element['xaxis']['#labels'] = $day_labels;
    $element['yaxis']['#type'] = 'chart_yaxis';
    $element['#raw_options']['options']['plugins']['datalabels']['color'] = Colors::NeutralDarkest->value;
    $element['#raw_options']['options']['plugins']['datalabels']['anchor'] = 'end';
    $element['#raw_options']['options']['plugins']['datalabels']['align'] = 'top';
    $element['#raw_options']['options']['scales']['y']['ticks']['stepSize'] = 1;
  }

  /**
   * Resolve date range from exposed filter values.
   */
  protected function resolveDateRange(?Request $request): array {
    $now = new \DateTimeImmutable('today');
    $from = $this->parseExposedDate((string) ($request?->query->get('date[min]') ?? ''));
    $to = $this->parseExposedDate((string) ($request?->query->get('date[max]') ?? ''));
    if (!$from && !$to) {
      $from = $now->modify('-6 days');
      $to = $now;
    }
    elseif ($from && !$to) {
      $to = $from;
    }
    elseif (!$from && $to) {
      $from = $to;
    }
    return [$from, $to];
  }

  /**
   * Parse a date string from exposed filters.
   */
  protected function parseExposedDate(string $value): ?\DateTimeImmutable {
    $value = trim($value);
    if ($value === '') {
      return NULL;
    }
    foreach (['Y-m-d', 'd/m/Y', 'Y-m-d\TH:i:s'] as $format) {
      $date = \DateTimeImmutable::createFromFormat($format, $value);
      if ($date instanceof \DateTimeImmutable) {
        return $date;
      }
    }
    $timestamp = strtotime($value);
    return $timestamp ? (new \DateTimeImmutable())->setTimestamp($timestamp) : NULL;
  }

  /**
   * Get bar color.
   *
   * @param string $state
   *   State label.
   *
   * @return string
   *   Color HEX code.
   */
  protected function getColor(string $state): string {
    if (!self::$reverseResultOptions) {
      $options = Execution::getResultAllowedValues();
      $reverse_options = [];
      foreach ($options as $key => $value) {
        $reverse_options[(string) $value] = $key;
      }
      self::$reverseResultOptions = $reverse_options;
    }
    $raw_state = self::$reverseResultOptions[$state] ?? NULL;
    return match ($raw_state) {
      ExecutionResult::Success->value => '#198754',
      ExecutionResult::Failure->value => '#dc3545',
      ExecutionResult::Missed->value => '#6c757d',
      default => Colors::Neutral->value,
    };
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_views_exposed_form_alter')]
  public function viewsExposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $view = $form_state->get('view');
    if (!$view instanceof ViewExecutable) {
      return;
    }
    if (!in_array($view->id(), [
      'executions_chart',
      'performance_chart',
    ])) {
      return;
    }
    if (isset($form['routine'])) {
      $this->modifyFilter($form['routine'], $this->filterOptionsRepository->getRoutineOptions());
    }
    if (isset($form['exercise'])) {
      $this->modifyFilter($form['exercise'], $this->filterOptionsRepository->getExerciseOptions());
    }
    if (isset($form['intensity'])) {
      $this->modifyFilter($form['intensity'], $this->filterOptionsRepository->getIntensityOptions());
    }

    if (in_array($view->id(), ['performance_chart', 'executions_chart'], TRUE)) {
      $form['patient'] = [
        '#type' => 'select',
        '#title' => $this->t('Paciente'),
        '#options' => $this->filterOptionsRepository->getPatientOptions(),
        '#empty_option' => $this->t('- Select -'),
        '#default_value' => $view->getExposedInput()['patient'] ?? NULL,
        '#weight' => -10,
      ];
      $form['chart_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Gráfica'),
        '#options' => [
          'totals' => $this->t('Resultados totales'),
          'evolution' => $this->t('Evolución en el tiempo'),
        ],
        '#default_value' => $view->getExposedInput()['chart_type'] ?? 'totals',
        '#weight' => -9,
      ];
    }
  }

  /**
   * Implements hook_views_query_alter().
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, Sql $query): void {
    if ($view->id() !== 'performance_chart') {
      return;
    }
    $exposed = $view->getExposedInput();
    $patient = (int) ($exposed['patient'] ?? 0);
    if ($patient <= 0) {
      return;
    }
    $query->addWhere(0, 'node__field_patient.field_patient_target_id', $patient, '=');
  }

  /**
   * Transform filter to select.
   *
   * @param array $filter
   *   Filter render array.
   * @param array $options
   *   Options.
   */
  protected function modifyFilter(array &$filter, array $options): void {
    $filter['#options'] = $options;
    $filter['#type'] = 'select';
    $filter['#multiple'] = FALSE;
    unset($filter['#size'], $filter['#process'], $filter['#pre_render']);
    $filter['#empty_option'] = $this->t('- Select -');
    if (empty($filter['#default_value'])) {
      $filter['#default_value'] = NULL;
    }
  }

  /**
   * Implements hook_chart_alter().
   */
  #[Hook('chart_performance_chart_performance_chart_alter')]
  public function performanceChartAlter(array &$element): void {
    $request = \Drupal::requestStack()->getCurrentRequest();
    $selected_chart = $request?->query->get('chart_type', 'totals') ?? 'totals';

    $series = [];
    foreach (Element::children($element) as $key) {
      $type = $element[$key]['#type'] ?? NULL;
      if ($type !== 'chart_data') {
        continue;
      }
      $key_parts = explode('__', $key);
      $serie = $key_parts[0];

      $dataset_type = $element[$key]['#chart_type'] ?? '';
      if ($dataset_type === 'line') {
        $series[$serie][] = $key;
        $data = [];
        foreach ($element[$key]['#data'] as $data_key => $item) {
          $data[] = $element[$key]['#mapped_data'][$data_key] ?? 0;
        }
        $element[$key]['#data'] = $data;
        $element[$key]['#mapped_data'] = $data;
      }
      if ($selected_chart === 'evolution' && $dataset_type !== 'line') {
        $element[$key]['#chart_type'] = 'line';
      }
      $opacity = $dataset_type === 'line' ? 'FF' : 'AA';
      $element[$key]['#color'] = $this->getColor($element[$key]['#title']) . $opacity;
    }
    $count = count($element['xaxis']['#labels']);
    foreach ($series as $keys) {
      for ($i = 0; $i < $count; $i++) {
        $data = [];
        foreach ($keys as $key) {
          $data[$key] = $element[$key]['#mapped_data'][$i] ?? 0;
        }
        $sum = array_sum($data);
        if (!$sum) {
          continue;
        }
        $normalized = array_map(static fn($value) => round($value / $sum * 100), $data);
        foreach ($normalized as $key => $value) {
          $element[$key]['#data'][$i] = $value;
        }
      }
    }
  }

  /**
   * Implements hook_chart_definition_CHART_ID_alter().
   */
  #[Hook('chart_definition_performance_chart_performance_chart_alter')]
  public function chartDefinitionPerformanceChartAlter(array &$definition, array $element, string $chart_id): void {
    foreach ($definition['data']['datasets'] as $key => $data) {
      $type = $data['type'] ?? '';
      if ($type === 'line') {
        $definition['data']['datasets'][$key]['stack'] = $key;
        $definition['data']['datasets'][$key]['order'] = 5;
        $definition['data']['datasets'][$key]['yAxisID'] = 'right';
      }
    }
    $definition['options']['scales']['right'] = [
      'position' => 'right',
      'grid' => [
        'drawOnChartArea' => FALSE,
      ],
    ];
  }

}
