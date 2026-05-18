<?php

declare(strict_types=1);

namespace Drupal\citius_content\Plugin\Field\FieldFormatter;

use Drupal\citius_content\ParagraphBundles;
use Drupal\citius_content\ParagraphFields;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'Exercise table' formatter.
 *
 * @extends \Drupal\Core\Field\FormatterBase<\Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList>
 */
#[FieldFormatter(
  id: 'citius_content_exercise_table',
  label: new TranslatableMarkup('Exercise table'),
  field_types: ['entity_reference_revisions'],
)]
class ExerciseTableFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    $target_bundles = $field_definition->getSetting('handler_settings')['target_bundles'] ?? [];
    return count($target_bundles) === 1 && reset($target_bundles) === ParagraphBundles::EXERCISE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    if ($items->isEmpty()) {
      return [];
    }
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Exercise'),
        $this->t('Duration'),
        $this->t('Intensity'),
      ],
      '#attributes' => [
        'class' => ['exercise-table'],
      ],
    ];
    $rows = [];
    $view_settings = ['label' => 'hidden'];
    $fields = [
      ParagraphFields::EXERCISE,
      ParagraphFields::DURATION,
      ParagraphFields::INTENSITY,
    ];
    /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem<\Drupal\paragraphs\ParagraphInterface> $item */
    foreach ($items as $item) {
      /** @var \Drupal\paragraphs\ParagraphInterface|null $exercise */
      $exercise = $item->entity;
      $row = [];
      foreach ($fields as $field) {
        $row[] = [
          'data' => $exercise?->get($field)->view($view_settings),
        ];
      }
      $rows[] = $row;
    }
    $table['#rows'] = $rows;
    return [$table];
  }

}
