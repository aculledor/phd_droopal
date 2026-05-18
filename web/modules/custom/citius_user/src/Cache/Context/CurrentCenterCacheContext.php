<?php

declare(strict_types=1);

namespace Drupal\citius_user\Cache\Context;

use Drupal\citius_user\CurrentCenterResolver;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Current center cache context.
 */
final readonly class CurrentCenterCacheContext implements CacheContextInterface {

  public const string CACHE_CONTEXT_ID = 'current_center';

  public function __construct(
    private CurrentCenterResolver $currentCenterResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): string {
    return (string) t('Current center');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): string {
    $center = $this->currentCenterResolver->get();
    return $center ? (string) $center : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    return new CacheableMetadata();
  }

}
