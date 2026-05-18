<?php

namespace Drupal\entity_bundle_permissions;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates dynamic bundle-specific permissions for each entity type.
 *
 * Copyright (C) 2024  Library Solutions, LLC (et al.).
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @internal
 */
class DynamicPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a DynamicPermissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface|null $config_factory
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, ?ConfigFactoryInterface $config_factory = NULL) {
    if ($config_factory === NULL) {
      @\trigger_error('Calling ' . __METHOD__ . '() without the $config_factory argument is deprecated in entity_bundle_permissions:1.1.0 and will be required in entity_bundle_permissions:2.0.0. See https://www.drupal.org/node/3409810', \E_USER_DEPRECATED);

      // @phpcs:disable DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
      // @phpstan-ignore-next-line
      $config_factory = \Drupal::configFactory();
      // @phpcs:enable
    }

    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * Check if permissions should be generated for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type_definition
   *   The entity type definition to check.
   *
   * @return bool
   *   TRUE if this module applies, FALSE otherwise.
   */
  public function applies(EntityTypeInterface $entity_type_definition) {
    if ($entity_type_definition->isInternal()) {
      return FALSE;
    }

    if (!$entity_type_definition->getBundleEntityType()) {
      return FALSE;
    }

    if (!$entity_type_definition instanceof ContentEntityTypeInterface) {
      return FALSE;
    }

    if (\in_array($entity_type_definition->id(), $this->getIgnoredEntityTypeIds(), TRUE)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
    );
  }

  /**
   * Get the dynamic bundle-specific permissions for each entity type.
   *
   * @return array
   *   The dynamic bundle-specific permissions for each entity type.
   */
  public function get(): array {
    $permissions = [];

    foreach ($this->getApplicableContentEntityTypeDefinitions() as $entity_type_definition) {
      foreach ($this->getBundlesForEntityTypeDefinition($entity_type_definition) as $bundle) {
        $permissions["entity_bundle_permissions access {$entity_type_definition->id()} {$bundle->id()}"] = [
          'title' => $this->t('Access %bundle_label @entity_type_label', [
            '%bundle_label' => $bundle->label(),
            '@entity_type_label' => $entity_type_definition->getPluralLabel(),
          ]),
          'description' => $this->t('Granting this permission will not imbue any additional access; instead, access will only be further restricted for those who lack this permission.'),
          'dependencies' => [
            $bundle->getConfigDependencyKey() => [
              $bundle->getConfigDependencyName(),
            ],
          ],
        ];
      }
    }

    return $permissions;
  }

  /**
   * Get a list of applicable content entity type definitions.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface[]
   *   A list of applicable content entity type definitions.
   */
  protected function getApplicableContentEntityTypeDefinitions(): array {
    return \array_filter($this->entityTypeManager->getDefinitions(), $this->applies(...));
  }

  /**
   * Get a list of bundle entities for the supplied entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type_definition
   *   The entity type for which to get a list of bundle entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   A list of bundle entities for the supplied entity type definition.
   */
  protected function getBundlesForEntityTypeDefinition(EntityTypeInterface $entity_type_definition): array {
    try {
      if ($bundle_entity_type_id = $entity_type_definition->getBundleEntityType()) {
        return $this->entityTypeManager->getStorage($bundle_entity_type_id)->loadMultiple();
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException) {
      // This point should never be reached, but log a message just in case.
      $this->logger()->warning('Unable to load the bundle entity type storage class for @entity_type_id; skipping dynamic permission generation', [
        '@entity_type_id' => $entity_type_definition->id(),
      ]);
    }

    return [];
  }

  /**
   * Get a list of ignored entity type IDs.
   *
   * @return string[]
   *   A list of ignored entity type IDs.
   */
  protected function getIgnoredEntityTypeIds(): array {
    $config = $this->configFactory->get('entity_bundle_permissions.settings');
    $ignored_entity_types = $config->get('ignored_entity_types');

    if (\is_array($ignored_entity_types)) {
      return \array_filter($ignored_entity_types);
    }

    return [];
  }

  /**
   * Get the logger channel for this module.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   The logger channel for this module.
   */
  protected function logger(): LoggerChannelInterface {
    return $this->loggerFactory->get('entity_bundle_permissions');
  }

}
