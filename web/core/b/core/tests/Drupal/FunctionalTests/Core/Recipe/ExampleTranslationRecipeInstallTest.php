<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Recipe;

use Drupal\FunctionalTests\Installer\InstallerTestBase;
// use Drupal\Tests\standard\Traits\StandardTestTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

/**
 * Tests installing the Example recipe via the installer.
 *
 * @group #slow
 * @group Recipe
 */
class ExampleTranslationRecipeInstallTest extends InstallerTestBase {
  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = '';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Skip permissions hardening so we can write a services file later.
    $this->settings['settings']['skip_permissions_hardening'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller(): void {
    // Use a URL to install from a recipe.
    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php' . '?profile=&recipe=core/recipes/example_translation');
  }

  /**
   * {@inheritdoc}
   */
  public function testExampleTranslation(): void {
    // Check if we have translated config and that the value for the translated
    // description is the same we set in the example_translation recipe.
    $language_manager = $this->container->get('language_manager');
    $translated_config = $language_manager->getLanguageConfigOverride('fr', 'node.type.article');
    $this->assertSame('Description (fr).', $translated_config->get('description'), 'The description is translated.');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile(): void {
    // Noop. This form is skipped due the parameters set on the URL.
  }

  protected function installDefaultThemeFromClassProperty(ContainerInterface $container): void {
    // In this context a default theme makes no sense.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite(): void {
    $services_file = DRUPAL_ROOT . '/' . $this->siteDirectory . '/services.yml';
    // $content = file_get_contents($services_file);

    // Disable the super user access.
    $yaml = new SymfonyYaml();
    $services = [];
    $services['parameters']['security.enable_super_user'] = FALSE;
    file_put_contents($services_file, $yaml->dump($services));
    parent::setUpSite();
  }

}
