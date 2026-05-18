<?php

namespace Drupal\Tests\json_field\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;

/**
 * @covers \Drupal\json_field\Plugin\Field\FieldFormatter\PrettyFormatter
 *
 * @group json_field
 */
class PrettyFormatterTest extends KernelTestBase {

  /**
   * Tests that the formatter is rendering an empty array.
   */
  public function testFormatter() {
    $this->createTestField();

    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $entity_view_display->setComponent('test_json_field', ['type' => 'pretty']);
    $entity_view_display->save();

    $entity = EntityTest::create([
      'test_json_field' => json_encode(['foo' => 'bar']),
    ]);
    $entity->save();

    $build = $entity_view_display->build($entity);

    $content = (string) $this->container->get('renderer')->renderPlain($build);
    self::assertSame('<div class="json-field-pretty"><dl><dt>foo</dt><dd>bar</dd></dl></div>', $content);
  }

}
