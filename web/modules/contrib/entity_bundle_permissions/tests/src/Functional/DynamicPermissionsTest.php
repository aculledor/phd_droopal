<?php

namespace Drupal\Tests\entity_bundle_permissions\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests dynamic permissions in the browser.
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
class DynamicPermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_bundle_permissions',
    'node',
  ];

  /**
   * Test that the entity permissions form contains this module's permissions.
   */
  public function testBundleSpecificManagePermissionsTab(): void {
    /** @var \Drupal\node\NodeTypeInterface */
    $content_type = $this->drupalCreateContentType();

    $entity_type_id = $content_type->getEntityType()->getBundleOf();
    $bundle = $content_type->id();

    $this->drupalLogin($this->drupalCreateUser([
      'administer permissions',
    ]));

    $anonymous_role = RoleInterface::ANONYMOUS_ID;
    $permission = "{$anonymous_role}[entity_bundle_permissions access {$entity_type_id} {$bundle}]";

    $this->drupalGet($content_type->toUrl('entity-permissions-form'));
    $this->assertSession()->fieldExists($permission);
  }

}
