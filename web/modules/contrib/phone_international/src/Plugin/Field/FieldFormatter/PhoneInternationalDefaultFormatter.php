<?php

declare(strict_types = 1);

namespace Drupal\phone_international\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Drupal\phone_international\Helpers\IsValidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'phone_international_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "phone_international_formatter",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "phone_international"
 *   }
 * )
 */
class PhoneInternationalDefaultFormatter extends FormatterBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\phone_international\Helpers\IsValidInterface
   */
  protected IsValidInterface $phoneInternational;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\phone_international\Helpers\IsValidInterface $phone_international
   *   Service to validate international phone numbers.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, IsValidInterface $phone_international) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->phoneInternational = $phone_international;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('phone_international.validate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $phone_number = $this->viewValue($item);
      if ($this->phoneInternational->isValidNumber($phone_number)) {
        // Render each element as link.
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $phone_number,
          // Prepend 'tel:' to the telephone number.
          '#url' => Url::fromUri('tel:' . rawurlencode($phone_number)),
          '#options' => ['external' => TRUE],
        ];
      }
      else {
        // Invalid link so render as text.
        $elements[$delta] = [
          '#markup' => $phone_number,
        ];
      }

      if (!empty($item->_attributes)) {
        $elements[$delta]['#options'] += ['attributes' => []];
        $elements[$delta]['#options']['attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item): string {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
