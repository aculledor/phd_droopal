<?php

declare(strict_types = 1);

namespace Drupal\Tests\phone_international\Kernel;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the phone_international field type.
 *
 * @group phone_international
 */
class PhoneInternationalItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['phone_international'];

  /**
   * Tests using entity fields of the phone_international field type.
   */
  public function testTestItem() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = '+0123456789';
    $entity->field_test = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_test instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEquals($entity->field_test->value, $value);
    $this->assertEquals($entity->field_test[0]->value, $value);

    // Verify changing the field value.
    $new_value = '+41' . rand(1000000, 9999999);
    $entity->field_test->value = $new_value;
    $this->assertEquals($entity->field_test->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEquals($entity->field_test->value, $new_value);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a phone_international field storage and field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'phone_international',
      '#attributes' => [
        'data-country' => 'PT',
        'data-geo' => 0,
        'data-exclude' => [],
        'data-preferred' => ['PT'],
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ])->save();
  }

}
