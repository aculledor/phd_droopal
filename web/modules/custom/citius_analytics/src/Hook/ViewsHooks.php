<?php

namespace Drupal\citius_analytics\Hook;

use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\NodeFields;
use Drupal\citius_content\ParagraphFields;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ViewExecutable;

/**
 * Hooks related to views.
 */
class ViewsHooks {

  use StringTranslationTrait;

  /**
   * Maximum number of sessions to show.
   */
  private const int MAX_SESSIONS = 7;

  /**
   * Static cache for last sessions ID's.
   *
   * @var array|null
   */
  private static ?array $sessions = NULL;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    if (isset($data['users_field_data'])) {
      $data['users_field_data']['statistics_icon_buttons'] = [
        'title' => $this->t('Statistics icon buttons'),
        'help' => $this->t('Displays statistics links as icons.'),
        'field' => [
          'id' => 'statistics_icon_buttons',
        ],
      ];
    }
  }

  /**
   * Implements hook_views_query_alter().
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query): void {
    if (($view->current_display !== 'chart_extension_progress') && $view->id() !== 'performance_chart') {
      return;
    }
    $uid = $view->args[0] ?? NULL;
    if (!$uid) {
      return;
    }
    if (self::$sessions === NULL) {
      $session_query = $this->entityTypeManager->getStorage('node')->getQuery();
      $session_query
        ->condition('type', NodeBundles::SESSION)
        ->condition(NodeFields::PATIENT, $uid)
        ->exists(NodeFields::DATE)
        ->sort(NodeFields::DATE, 'DESC')
        ->range(0, self::MAX_SESSIONS);
      $date_filter = $view->exposed_raw_input['date'] ?? [];
      $date_filter = array_filter($date_filter);
      if (!empty($date_filter)) {
        $session_query->condition(NodeFields::DATE, $date_filter, 'BETWEEN');
      }
      $exercise_filter = $view->exposed_raw_input['exercise'] ?? NULL;
      if (!empty($exercise_filter)) {
        $session_query->condition(NodeFields::ROUTINE . '.entity.' . NodeFields::EXERCISES . '.entity.' . ParagraphFields::EXERCISE, $exercise_filter);
      }
      $intensity_filter = $view->exposed_raw_input['intensity'] ?? NULL;
      if (!empty($intensity_filter)) {
        $session_query->condition(NodeFields::ROUTINE . '.entity.' . NodeFields::EXERCISES . '.entity.' . ParagraphFields::INTENSITY, $intensity_filter);
      }
      $sessions = $session_query->accessCheck(FALSE)->execute();
      self::$sessions = $sessions;
    }

    if (!empty(self::$sessions) && $query instanceof Sql) {
      $query->addWhere(1, 'node_field_data_execution.nid', self::$sessions, 'IN');
    }
  }

}
