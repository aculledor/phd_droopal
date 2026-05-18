<?php

namespace Drupal\custom_paragraphs_extras\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Provides a custom paragraph behavior to set background color.
 *
 * @ParagraphsBehavior(
 *   id = "custom_alignment_behavior",
 *   label = @Translation("Alignment behavior"),
 *   description = @Translation("Add an alignment selector to paragraphs with 2 columns."),
 *   weight = 1,
 * )
 */
class AlignmentBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $form['#attributes']['class'][] = 'paragraphs-subform';
    $form['alignment'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Alignment'),
      '#options'       => $this->getOptions(),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'alignment', 'normal'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode): void {
    if ($value = $paragraph->getBehaviorSetting($this->getPluginId(), 'alignment', 'normal')) {
      $build['#attributes']['class'][] = 'component-alignment-' . $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph): array|null {
    if ($value = $paragraph->getBehaviorSetting($this->getPluginId(), 'alignment')) {
      return [$this->t('Alignment: @alignment', ['@alignment' => $this->getOptions()[$value]])];
    }
    return NULL;
  }

  /**
   * Get the options for the alignment select field.
   */
  protected function getOptions(): array {
    return [
      'normal'   => $this->t('Normal'),
      'inverted' => $this->t('Inverted'),
    ];
  }

}
