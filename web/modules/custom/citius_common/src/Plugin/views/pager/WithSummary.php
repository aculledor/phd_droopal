<?php

declare(strict_types=1);

namespace Drupal\citius_common\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsPager;
use Drupal\views\Plugin\views\pager\Full;

/**
 * The plugin to handle full pager with results summary.
 */
#[ViewsPager(
  id: "with_summary",
  title: new TranslatableMarkup("Full pager with summary"),
  short_title: new TranslatableMarkup("Full with summary"),
  help: new TranslatableMarkup("A full pager that displays a summary of results."),
  theme: "pager",
  register_theme: FALSE
)]
class WithSummary extends Full {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['summary_format'] = ['default' => 'Shows @start to @end out of @total'];
    $options['show_summary'] = ['default' => TRUE];
    return $options;
  }

  /**
   * Provide a form to edit options for this plugin.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $form['show_summary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show results summary'),
      '#description' => $this->t('Display a summary of the current results (e.g., "Shows 1 to 10 out of 15").'),
      '#default_value' => $this->options['show_summary'],
    ];

    $form['summary_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Summary format'),
      '#description' => $this->t('Format string for the results summary. Available tokens: @start, @end, @total'),
      '#default_value' => $this->options['summary_format'],
      '#states' => [
        'visible' => [
          ':input[name="pager_options[show_summary]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Return the renderable array of the pager.
   *
   * Called during the view render process.
   *
   * @param array $input
   *   Any extra GET parameters that should be retained, such as exposed
   *   input.
   */
  public function render($input): array {
    $pager = parent::render($input);

    if (empty($this->options['show_summary']) || !$this->getTotalItems()) {
      return $pager;
    }

    $current_page = $this->getCurrentPage();
    $items_per_page = $this->getItemsPerPage();
    $total_items = $this->getTotalItems();

    $start = ($current_page * $items_per_page) + 1;
    $end = min(($current_page + 1) * $items_per_page, $total_items);

    $replacements = [
      '@start' => $start,
      '@end' => $end,
      '@total' => $total_items,
    ];

    $summary = str_replace(array_keys($replacements), array_values($replacements), $this->options['summary_format']);

    return [
      '#theme' => 'pager_with_summary',
      '#pager' => $pager,
      '#summary' => $summary,
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['views_data'],
      ],
    ];
  }

}
