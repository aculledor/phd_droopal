<?php

namespace Drupal\Tests\entity_bundle_permissions\Functional;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestWithBundle;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests entity access.
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
class EntityAccessTest extends BrowserTestBase {

  const ENTITY_TEST_BUNDLE = 'entity_bundle_permissions_test';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_bundle_permissions',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $bundle = EntityTestBundle::create([
      'id' => self::ENTITY_TEST_BUNDLE,
    ]);

    $bundle->save();
  }

  /**
   * Data provider for ::testApplicableEntityAccess().
   */
  public function providerTestApplicableEntityAccess(): array {
    $bundle = self::ENTITY_TEST_BUNDLE;

    return [
      "has entity_bundle_permissions access entity_test_with_bundle {$bundle}" => [
        [
          "entity_bundle_permissions access entity_test_with_bundle {$bundle}",
        ],
        403,
      ],
      "has entity_bundle_permissions access entity_test_with_bundle {$bundle}, view test entity" => [
        [
          "entity_bundle_permissions access entity_test_with_bundle {$bundle}",
          'view test entity',
        ],
        200,
      ],
      'has view test entity' => [
        [
          'view test entity',
        ],
        403,
      ],
      'no permissions' => [
        [],
        403,
      ],
    ];
  }

  /**
   * Test that non-applicable entities are accessible.
   */
  public function testNonApplicableEntityAccess(): void {
    $user = $this->drupalCreateUser();

    $this->drupalLogin($user);
    $this->drupalGet($user->toUrl());

    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test applicable entity access.
   *
   * @dataProvider providerTestApplicableEntityAccess
   */
  public function testApplicableEntityAccess(array $permissions, int $status_code): void {
    $entity_test_with_bundle = EntityTestWithBundle::create([
      'type' => self::ENTITY_TEST_BUNDLE,
    ]);

    $entity_test_with_bundle->save();

    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalGet($entity_test_with_bundle->toUrl());

    $this->assertSession()->statusCodeEquals($status_code);
  }

}
