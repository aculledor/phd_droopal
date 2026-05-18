<?php

declare(strict_types=1);

namespace Drupal\citius_analytics;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Handles available options for analytics filters.
 */
final class FilterOptionsRepository {

  /**
   * Cache prefix.
   */
  private const string CACHE_PREFIX = 'citius_analytics_filter_options:';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    #[Autowire(service: 'cache.default')]
    protected CacheBackendInterface $cache,
  ) {}

  /**
   * Load ID's for routine filter.
   *
   * @return array
   *   Array of ID's.
   */
  protected function loadRoutineIds(): array {
    $query = $this->database
      ->select('node__field_routine', 'n');
    $query->fields('n', ['field_routine_target_id']);
    $query->distinct();
    return $query->execute()?->fetchCol() ?? [];
  }

  /**
   * Load ID's for exercise filter.
   *
   * @return array
   *   Array of ID's.
   */
  protected function loadExerciseIds(): array {
    $query = $this->database
      ->select('paragraph__field_exercise', 'p');
    $query->fields('p', ['field_exercise_target_id']);
    $query->distinct();
    return $query->execute()?->fetchCol() ?? [];
  }

  /**
   * Get available intensity values.
   *
   * @return array
   *   Array of intensity options.
   */
  protected function getIntensityValues(): array {
    $query = $this->database
      ->select('paragraph__field_intensity', 'p');
    $query->fields('p', ['field_intensity_value']);
    $query->orderBy('field_intensity_value');
    $query->distinct();
    return $query->execute()?->fetchCol() ?? [];
  }

  /**
   * Get node titles for filter options.
   *
   * @param array $ids
   *   Array of node IDs.
   *
   * @return array
   *   Array of node titles keyed by ID.
   */
  protected function getNodeOptions(array $ids): array {
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);
    $options = [];
    foreach ($nodes as $node) {
      $options[$node->id()] = $node->label();
    }
    return $options;
  }

  /**
   * Get available options for routine filter.
   *
   * @return array
   *   Array of routine options.
   */
  public function getRoutineOptions(): array {
    $cid = self::CACHE_PREFIX . 'routine';
    $cache_data = $this->cache->get($cid);
    if ($cache_data) {
      return $cache_data->data;
    }
    $options = $this->getNodeOptions($this->loadRoutineIds());
    $this->cache->set($cid, $options, Cache::PERMANENT, ['node_list:session']);
    return $options;
  }

  /**
   * Get available options for exercise filter.
   *
   * @return array
   *   Array of exercise options.
   */
  public function getExerciseOptions(): array {
    $cid = self::CACHE_PREFIX . 'exercise';
    $cache_data = $this->cache->get($cid);
    if ($cache_data) {
      return $cache_data->data;
    }
    $options = $this->getNodeOptions($this->loadExerciseIds());
    $this->cache->set($cid, $options, Cache::PERMANENT, ['node_list:session']);
    return $options;
  }

  /**
   * Get available options for intensity filter.
   *
   * @return array
   *   Array of intensity options.
   */
  public function getIntensityOptions(): array {
    $cid = self::CACHE_PREFIX . 'intensity';
    $cache_data = $this->cache->get($cid);
    if ($cache_data) {
      return $cache_data->data;
    }
    $values = $this->getIntensityValues();
    $options = array_combine($values, $values);
    $this->cache->set($cid, $options, Cache::PERMANENT, ['node_list:session']);
    return $options;
  }

}
