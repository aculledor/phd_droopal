<?php

namespace Drupal\Tests\entity_bundle_permissions\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form in the browser.
 *
 * Copyright (C) 2023  Library Solutions, LLC (et al.).
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @group entity_bundle_permissions
 */
class SettingsFormTest extends BrowserTestBase {

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
   * Test that the settings form removes non-existent permissions from roles.
   */
  public function testBundleSpecificManagePermissionsTab(): void {
    /** @var \Drupal\node\NodeTypeInterface */
    $content_type = $this->drupalCreateContentType();

    $entity_type_id = $content_type->getEntityType()->getBundleOf();
    $bundle = $content_type->id();

    $this->drupalLogin($this->drupalCreateUser([
      'administer entity_bundle_permissions',
      "entity_bundle_permissions access {$entity_type_id} {$bundle}",
    ]));

    $this->drupalGet('admin/config/entity-bundle-permissions');
    $form_values = [
      'ignored_entity_types[]' => [
        $entity_type_id,
      ],
    ];

    $this->submitForm($form_values, 'Save configuration');
    $this->assertSession()->statusMessageContains('The configuration options have been saved and 1 user role was updated.', 'status');
  }

}
