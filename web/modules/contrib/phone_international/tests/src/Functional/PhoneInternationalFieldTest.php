<?php

declare(strict_types = 1);

namespace Drupal\Tests\phone_international\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group phone_international
 */
class PhoneInternationalFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node', 'entity_test', 'field_ui', 'phone_international',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to create articles.
   */
  protected string $fieldname;

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'administer content types',
    ]));

    // Create a field with settings to validate.
    $this->fieldname = mb_strtolower($this->randomMachineName());
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldname,
      'entity_type' => 'entity_test',
      'type' => 'phone_international',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
    ]);
    $this->field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Create a form display for the default form mode.
    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($this->fieldname, [
        'type' => 'phone_international_widget',
        'settings' => [
          'geolocation' => 0,
          'initial_country' => 'PT',
          'countries' => 'exclude',
          'exclude_countries' => [],
          'preferred_countries' => [],
        ],
      ])
      ->save();
    // Create a display for the full view mode.
    $display_repository->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($this->fieldname, [
        'type' => 'phone_international_formatter',
      ])
      ->save();
  }

  /**
   * Test to confirm the widget is setup.
   */
  public function testTelephoneWidget(): void {
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$this->fieldname}[0][value][int_phone]", '');
  }

  /**
   * Test the phone_international formatter.
   *
   * @covers \Drupal\phone_international\Plugin\Field\FieldFormatter\PhoneInternationalDefaultFormatter::viewElements
   *
   * @dataProvider providerPhoneNumbers
   */
  public function testTelephoneFormatter($input, $expected) {

    $entity = EntityTest::create();
    $entity->{$this->fieldname}->value = $input;
    $entity->save();

    $id = $entity->id();
    $entity = EntityTest::load($id);

    // Verify entity value field is correct.
    $entity = EntityTest::load($entity->id());
    $this->assertEquals($expected, $entity->{$this->fieldname}->value);

    // Verify when page is load.
    $this->drupalGet($entity->toUrl());
    $this->assertSession()->responseContains($expected);
  }

  /**
   * Provides the phone numbers to check and expected results.
   */
  public function providerPhoneNumbers(): array {
    return [
      'standard phone number' => ['+123456789', '+123456789'],
      'standard phone number country' => ['+351123456789', '+351123456789'],
    ];
  }

}
