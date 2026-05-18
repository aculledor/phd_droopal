<?php

namespace Drupal\citius_common\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\system\Plugin\Block\SystemBrandingBlock;

/**
 * Provides a block to display 'Site branding' elements.
 *
 * @Block(
 *   id = "citius_branding_block",
 *   admin_label = @Translation("Citius Site branding"),
 *   forms = {
 *     "settings_tray" = "Drupal\system\Form\SystemBrandingOffCanvasForm",
 *   },
 * )
 */
class CitiusBrandingBlock extends SystemBrandingBlock {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The form elements.
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $url_system_site_information_settings = new Url('citius_common.settings');
    if ($url_system_site_information_settings->access()) {
      $site_information_url = $url_system_site_information_settings->toString();
      $site_address_description = new TranslatableMarkup(
        'Defined on the <a href="@information">CiTIUS Common Site Information</a> page.',
        ['@information' => $site_information_url]
      );
      $site_copyright_description = new TranslatableMarkup(
        'Defined on the <a href="@information">CiTIUS Common Site Information</a> page.',
        ['@information' => $site_information_url]
      );
    }
    else {
      $site_address_description = new TranslatableMarkup('Defined on the CiTIUS Common Site Information page. You do not have the appropriate permissions to change the site logo.');
      $site_copyright_description = new TranslatableMarkup('Defined on the CiTIUS Common Site Information page. You do not have the appropriate permissions to change the site logo.');
    }

    $form['block_branding']['use_site_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Site address'),
      '#description' => $site_address_description,
      '#default_value' => $this->configuration['use_site_address'] ?? 0,
    ];

    $form['block_branding']['use_site_copyright'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Site copyright'),
      '#description' => $site_copyright_description,
      '#default_value' => $this->configuration['use_site_copyright'] ?? 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @return void
   *   Submits the form and saves the configuration.
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $block_branding = $form_state->getValue('block_branding');
    $this->configuration['use_site_address'] = $block_branding['use_site_address'];
    $this->configuration['use_site_copyright'] = $block_branding['use_site_copyright'];
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The form elements.
   */
  public function build(): array {
    $build = parent::build();

    $site_config = $this->configFactory->get('citius_common.settings');

    $build['site_address'] = [
      '#markup' => $site_config->get('address'),
      '#access' => $this->configuration['use_site_address'],
    ];

    $build['site_copyright'] = [
      '#markup' => $site_config->get('copyright'),
      '#access' => $this->configuration['use_site_copyright'],
    ];

    foreach ($build as $key => $item) {
      if (isset($item['#access']) && $item['#access'] == 0) {
        unset($build[$key]);
      }
    }

    return $build;
  }

}
