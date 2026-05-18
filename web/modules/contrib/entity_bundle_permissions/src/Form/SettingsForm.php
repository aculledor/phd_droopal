<?php

namespace Drupal\entity_bundle_permissions\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\user\PermissionHandlerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form used to configure this module.
 *
 * Copyright (C) 2023  Library Solutions, LLC (et al.).
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @internal
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The permission handler service.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, EntityTypeManagerInterface $entity_type_manager, PermissionHandlerInterface $permission_handler) {
    parent::__construct($config_factory, $typed_config_manager);

    $this->entityTypeManager = $entity_type_manager;
    $this->permissionHandler = $permission_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(\current($this->getEditableConfigNames()));

    $form['ignored_entity_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Ignored entity types'),
      '#description' => $this->t('This module will not interfere with the entity types selected here.'),
      '#default_value' => $config->get('ignored_entity_types'),
      '#options' => $this->getEntityTypeOptions(),
      '#multiple' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('user.permissions'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_bundle_permissions.settings',
    ];
  }

  /**
   * Get a list of entity type options.
   *
   * @return array
   *   A list of entity type options.
   */
  protected function getEntityTypeOptions(): array {
    $options = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_definition) {
      if ($entity_type_definition instanceof ContentEntityTypeInterface && !$entity_type_definition->isInternal() && $entity_type_definition->getBundleEntityType()) {
        $options[$entity_type_definition->id()] = $entity_type_definition->getLabel();
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_bundle_permissions_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(\current($this->getEditableConfigNames()));
    $config->setData($form_state->cleanValues()->getValues());
    $config->save();

    $message = $this->t('The configuration options have been saved.');

    if ($user_roles_updated = $this->updateUserRoles()) {
      $message = $this->formatPlural($user_roles_updated, 'The configuration options have been saved and 1 user role was updated.', 'The configuration options have been saved and @count user roles were updated.');
    }

    $this->messenger()->addStatus($message);
  }

  /**
   * Update all user roles by removing the supplied permissions.
   */
  protected function updateUserRoles(): int {
    $all_permissions = $this->permissionHandler->getPermissions();
    $user_roles_updated = 0;

    /** @var \Drupal\user\RoleInterface */
    foreach ($this->entityTypeManager->getStorage('user_role')->loadMultiple() as $user_role) {
      $updated = FALSE;

      foreach ($user_role->getPermissions() as $permission) {
        if (!\array_key_exists($permission, $all_permissions)) {
          $user_role->revokePermission($permission);

          $updated = TRUE;
        }
      }

      if ($updated) {
        $user_role->save();
        ++$user_roles_updated;
      }
    }

    return $user_roles_updated;
  }

}
