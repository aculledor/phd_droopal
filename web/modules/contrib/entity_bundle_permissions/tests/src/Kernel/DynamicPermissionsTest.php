<?php

namespace Drupal\Tests\entity_bundle_permissions\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleInterface;

/**
 * Tests dynamic permission generation and expected behavior on install.
 *
 * Copyright (C) 2022  Library Solutions, LLC (et al.).
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @group entity_bundle_permissions
 */
class DynamicPermissionsTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->getModuleInstaller()->install([
      'user',
    ]);
  }

  /**
   * Get the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  protected function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->container->get('entity_type.manager');
  }

  /**
   * Get the module installer service.
   *
   * @return \Drupal\Core\Extension\ModuleInstallerInterface
   *   The module installer service.
   */
  protected function getModuleInstaller(): ModuleInstallerInterface {
    return $this->container->get('module_installer');
  }

  /**
   * Get the permission handler service.
   *
   * @return \Drupal\user\PermissionHandlerInterface
   *   The permission handler service.
   */
  protected function getPermissionHandler(): PermissionHandlerInterface {
    return $this->container->get('user.permissions');
  }

  /**
   * Test permission deletion when deleting a content entity bundle.
   */
  public function testPermissionDeletion(): void {
    $this->getModuleInstaller()->install(['node']);
    /** @var \Drupal\node\NodeTypeInterface */
    $content_type = $this->createContentType();
    $this->getModuleInstaller()->install(['entity_bundle_permissions']);

    $permission = "entity_bundle_permissions access {$content_type->getEntityType()->getBundleOf()} {$content_type->id()}";
    $content_type->delete();

    $this->assertArrayNotHasKey($permission, $this->getPermissionHandler()->getPermissions());
  }

  /**
   * Test permission generation for existing content entity bundles.
   */
  public function testPermissionGenerationForExistingBundles(): void {
    $this->getModuleInstaller()->install(['node']);
    /** @var \Drupal\node\NodeTypeInterface */
    $content_type = $this->createContentType();
    $this->getModuleInstaller()->install(['entity_bundle_permissions']);

    $permission = "entity_bundle_permissions access {$content_type->getEntityType()->getBundleOf()} {$content_type->id()}";
    $this->assertArrayHasKey($permission, $this->getPermissionHandler()->getPermissions());
  }

  /**
   * Test permission generation upon content entity bundle creation.
   */
  public function testPermissionGenerationOnBundleCreation(): void {
    $this->getModuleInstaller()->install([
      'entity_bundle_permissions',
      'node',
    ]);

    /** @var \Drupal\node\NodeTypeInterface */
    $content_type = $this->createContentType();

    $permission = "entity_bundle_permissions access {$content_type->getEntityType()->getBundleOf()} {$content_type->id()}";
    $this->assertArrayHasKey($permission, $this->getPermissionHandler()->getPermissions());
  }

  /**
   * Test that dynamic permissions are granted upon module installation.
   */
  public function testPermissionGrantsOnModuleInstall(): void {
    $this->getModuleInstaller()->install(['node']);
    /** @var \Drupal\node\NodeTypeInterface */
    $content_type = $this->createContentType();
    $this->getModuleInstaller()->install(['entity_bundle_permissions']);

    $user_role_storage = $this->getEntityTypeManager()->getStorage('user_role');
    /** @var \Drupal\user\RoleInterface[] */
    $roles = $user_role_storage->loadMultiple([
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
    ]);

    $permission = "entity_bundle_permissions access {$content_type->getEntityType()->getBundleOf()} {$content_type->id()}";
    foreach ($roles as $role) {
      $this->assertTrue($role->hasPermission($permission));
    }
  }

}
