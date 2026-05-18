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
use Drupal\views\ViewExecutable;

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
    foreach (Element::children($element) as $key) {
      $colors = [];
      $type = $element[$key]['#type'] ?? '';
      if ($type !== 'chart_data') {
        continue;
      }
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
      ExecutionResult::Success->value => Colors::Success->value,
      ExecutionResult::Failure->value => Colors::Error->value,
      ExecutionResult::Missed->value => Colors::NeutralLight->value,
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
    $series = [];
    foreach (Element::children($element) as $key) {
      $type = $element[$key]['#type'] ?? NULL;
      if ($type !== 'chart_data') {
        continue;
      }
      $key_parts = explode('__', $key);
      $serie = $key_parts[0];

      $chart_type = $element[$key]['#chart_type'] ?? '';
      if ($chart_type === 'line') {
        $series[$serie][] = $key;
        $data = [];
        foreach ($element[$key]['#data'] as $data_key => $item) {
          $data[] = $element[$key]['#mapped_data'][$data_key] ?? 0;
        }
        $element[$key]['#data'] = $data;
        $element[$key]['#mapped_data'] = $data;
      }
      $opacity = $chart_type === 'line' ? 'FF' : 'AA';
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
