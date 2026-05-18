<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Entity;

use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\ParagraphBundles;
use Drupal\citius_device_api\Entity\Handler\ExecutionHtmlRouteProvider;
use Drupal\citius_device_api\Entity\Handler\ExecutionListBuilder;
use Drupal\citius_device_api\Entity\Handler\ExecutionViewsData;
use Drupal\citius_device_api\ExecutionResult;
use Drupal\citius_device_api\Form\ExecutionForm;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the execution entity class.
 */
#[ContentEntityType(
  id: 'execution',
  label: new TranslatableMarkup('Execution'),
  label_collection: new TranslatableMarkup('Executions'),
  label_singular: new TranslatableMarkup('execution'),
  label_plural: new TranslatableMarkup('executions'),
  entity_keys: [
    'id' => 'id',
    'label' => 'id',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ExecutionListBuilder::class,
    'views_data' => ExecutionViewsData::class,
    'form' => [
      'add' => ExecutionForm::class,
      'edit' => ExecutionForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => ExecutionHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/execution',
    'add-form' => '/admin/config/citius/execution/add',
    'canonical' => '/admin/config/citius/execution/{execution}',
    'edit-form' => '/admin/config/citius/execution/{execution}',
    'delete-form' => '/admin/config/citius/execution/{execution}/delete',
    'delete-multiple-form' => '/admin/config/citius/execution/delete-multiple',
  ],
  admin_permission: 'administer execution',
  base_table: 'execution',
  label_count: [
    'singular' => '@count executions',
    'plural' => '@count executions',
  ],
  constraints: [
    'ExecutionParagraph' => [],
  ],
)]
class Execution extends ContentEntityBase implements ExecutionInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['session'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Session'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          NodeBundles::SESSION => NodeBundles::SESSION,
        ],
        'auto_create' => FALSE,
      ])
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -5,
      ]);

    $fields['exercise'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Exercise'))
      ->setSetting('target_type', 'paragraph')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          ParagraphBundles::EXERCISE => ParagraphBundles::EXERCISE,
        ],
        'auto_create' => FALSE,
      ])
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['result'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Result'))
      ->setRequired(FALSE)
      ->setSetting('allowed_values_function', [static::class, 'getResultAllowedValues'])
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['execution_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Execution Date'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['json_data'] = BaseFieldDefinition::create('json_native')
      ->setLabel(t('Execution Data'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'json_textarea',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'json',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $directions = [
      'head' => t('Head'),
      'left' => t('Left'),
      'right' => t('Right'),
    ];
    $coordinates = ['x', 'y', 'z'];

    foreach ($directions as $direction => $direction_label) {
      foreach ($coordinates as $coordinate) {
        $field_name = sprintf('%s_%s', $direction, $coordinate);
        $fields[$field_name] = BaseFieldDefinition::create('float')
          ->setLabel(t('@direction @coordinate', [
            '@direction' => $direction_label,
            '@coordinate' => strtoupper($coordinate),
          ]))
          ->setRequired(TRUE)
          ->setDisplayOptions('form', [
            'type' => 'number',
            'weight' => 0,
          ])
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayOptions('view', [
            'label' => 'inline',
            'type' => 'number_decimal',
            'weight' => 0,
          ])
          ->setDisplayConfigurable('view', TRUE);
      }
    }

    return $fields;
  }

  /**
   * Gets allowed values for the result field.
   *
   * @return array
   *   Array of allowed values keyed by machine name.
   */
  public static function getResultAllowedValues(): array {
    return [
      ExecutionResult::Success->value => t('Success'),
      ExecutionResult::Failure->value => t('Failure'),
      ExecutionResult::Missed->value => t('Missed'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSession(): ?EntityInterface {
    return $this->get('session')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setSession(?EntityInterface $session): ExecutionInterface {
    $this->set('session', $session);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExercise(): ?EntityInterface {
    return $this->get('exercise')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setExercise(?EntityInterface $exercise): ExecutionInterface {
    $this->set('exercise', $exercise);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(): ?string {
    return $this->get('result')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultLabel(): TranslatableMarkup|string {
    $result = $this->getResult();
    return self::getResultAllowedValues()[$result] ?? '-';
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(?string $result): ExecutionInterface {
    $this->set('result', $result);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutionDate(): ?DrupalDateTime {
    $value = $this->get('execution_date')->value;
    return $value ? new DrupalDateTime($value) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setExecutionDate(?DrupalDateTime $execution_date): ExecutionInterface {
    $this->set('execution_date', $execution_date?->format('Y-m-d\TH:i:s'));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsonData(): ?array {
    $value = $this->get('json_data')->value;
    return $value ? Json::decode($value) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setJsonData(?array $json_data): ExecutionInterface {
    $this->set('json_data', $json_data ? Json::encode($json_data) : NULL);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoordinates(): array {
    $coordinates = [
      'head' => ['x' => 0.0, 'y' => 0.0, 'z' => 0.0],
      'left' => ['x' => 0.0, 'y' => 0.0, 'z' => 0.0],
      'right' => ['x' => 0.0, 'y' => 0.0, 'z' => 0.0],
    ];

    $directions = ['head', 'left', 'right'];
    $axes = ['x', 'y', 'z'];

    foreach ($directions as $direction) {
      foreach ($axes as $axis) {
        $field_name = sprintf('%s_%s', $direction, $axis);
        $value = $this->get($field_name)->value;
        $coordinates[$direction][$axis] = $value !== NULL ? (float) $value : 0.0;
      }
    }

    return $coordinates;
  }

  /**
   * {@inheritdoc}
   */
  public function setCoordinates(array $coordinates): ExecutionInterface {
    $directions = ['head', 'left', 'right'];
    $axes = ['x', 'y', 'z'];

    foreach ($directions as $direction) {
      foreach ($axes as $axis) {
        $field_name = sprintf('%s_%s', $direction, $axis);
        $value = $coordinates[$direction][$axis] ?? 0.0;
        $this->set($field_name, (float) $value);
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string|TranslatableMarkup {
    return $this->getSession()?->label() ?? '';
  }

}
