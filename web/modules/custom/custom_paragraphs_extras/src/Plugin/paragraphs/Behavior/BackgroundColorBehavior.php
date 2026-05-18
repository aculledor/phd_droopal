<?php

namespace Drupal\custom_paragraphs_extras\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\custom_color_field\Element\Color;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom paragraph behavior to set background color.
 *
 * @ParagraphsBehavior(
 *   id = "custom_background_color_behavior",
 *   label = @Translation("Background color behavior"),
 *   description = @Translation("Adds a 'background color' field to the Paragraph entity."),
 *   weight = 0,
 * )
 */
class BackgroundColorBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, protected RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): BackgroundColorBehavior {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $form['#attributes']['class'][] = 'paragraphs-subform';
    $form['background_color'] = [
      '#type'          => 'custom_color',
      '#title'         => $this->t('Background color'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'background_color'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode): void {
    if (($value = $paragraph->getBehaviorSetting($this->getPluginId(), 'background_color')) && $value !== 'transparent' && Color::isValidColor($value)) {
      $build['#attributes']['class'][] = 'component-background-color component-background-color-full-width component-background-color-' . $value;
      $build['#attached']['library'][] = 'custom_paragraphs_extras/behaviors.background_color.view';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph): array|null {
    if (($value = $paragraph->getBehaviorSetting($this->getPluginId(), 'background_color')) && $value !== 'transparent' && Color::isValidColor($value)) {
      $colors = Color::getColors();
      $color_render_array = [
        '#type'     => 'inline_template',
        '#template' => '<span class="component-background-color-preview" style="background-color:' . $colors[$value]['code'] . '">' . $value . '</span>',
        '#attached' => [
          'library' => [
            'custom_paragraphs_extras/behaviors.background_color.summary',
          ],
        ],
      ];
      $color = $this->renderer->render($color_render_array);
      return [$this->t('Background color: @color', ['@color' => $color])];
    }
    return NULL;
  }

}
