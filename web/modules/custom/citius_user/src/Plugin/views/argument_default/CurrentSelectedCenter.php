<?php

declare(strict_types=1);

namespace Drupal\citius_user\Plugin\views\argument_default;

use Drupal\citius_user\Cache\Context\CurrentCenterCacheContext;
use Drupal\citius_user\CurrentCenterResolver;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsArgumentDefault;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets the default argument for the contextual filter.
 */
#[ViewsArgumentDefault(
  id: 'current_selected_center',
  title: new TranslatableMarkup('Current selected center'),
)]
final class CurrentSelectedCenter extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * Current center resolver.
   *
   * @var \Drupal\citius_user\CurrentCenterResolver
   */
  protected CurrentCenterResolver $currentCenterResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentCenterResolver = $container->get(CurrentCenterResolver::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument(): int|string {
    return $this->currentCenterResolver->get() ?? 'all';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return [
      'session',
      CurrentCenterCacheContext::CACHE_CONTEXT_ID,
    ];
  }

}
