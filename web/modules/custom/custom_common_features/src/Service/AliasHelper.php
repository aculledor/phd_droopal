<?php

namespace Drupal\custom_common_features\Service;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Helper class to manage aliases.
 */
class AliasHelper {

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel
   *   Logger channel.
   * @param \Drupal\path_alias\AliasRepositoryInterface $aliasRepository
   *   Alias repository.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_channel, protected AliasRepositoryInterface $aliasRepository) {
    $this->logger = $logger_channel->get('custom_common_features');
  }

  /**
   * Generate aliases for given languages.
   *
   * @param array $aliases
   *   Aliases to generate.
   */
  public function generateAliases(array $aliases): void {
    foreach ($aliases as $path => $data) {
      foreach ($data as $language => $alias) {
        $results = $this->aliasRepository->lookupBySystemPath($path, $language);
        if (!empty($results)) {
          continue;
        }

        $path_alias = PathAlias::create([
          'alias'    => $alias,
          'path'     => $path,
          'langcode' => $language,
        ]);
        try {
          $path_alias->save();
        }
        catch (EntityStorageException $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }
  }

}
