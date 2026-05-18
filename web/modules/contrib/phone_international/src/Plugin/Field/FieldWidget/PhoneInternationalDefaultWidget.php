<?php

declare(strict_types = 1);

namespace Drupal\phone_international\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'phone_international_widget' widget.
 *
 * @FieldWidget(
 *   id = "phone_international_widget",
 *   module = "phone_international",
 *   label = @Translation("Phone international field"),
 *   field_types = {
 *     "phone_international"
 *   }
 * )
 */
class PhoneInternationalDefaultWidget extends WidgetBase {

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected CountryManagerInterface $countryManager;

  /**
   * Constructs a RegionalForm object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CountryManagerInterface $country_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->countryManager = $country_manager;
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
      $configuration['third_party_settings'],
      $container->get('country_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'countries' => 'exclude',
      'exclude_countries' => [],
      'geolocation' => FALSE,
      'initial_country' => 'PT',
      'preferred_countries' => ['PT'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['value'] = $element + [
      '#type' => 'phone_international',
      '#default_value' => $items[$delta]->value ?? NULL,
      '#country' => $this->getSetting('initial_country'),
      '#geolocation' => (bool) $this->getSetting('geolocation'),
      '#exclude_countries' => $this->getSetting('exclude_countries'),
      '#countries' => $this->getSetting('countries'),
      '#preferred_countries' => $this->getSetting('preferred_countries'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = [];

    $elements['geolocation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Geolocation'),
      '#default_value' => $this->getSetting('geolocation'),
    ];

    $countries = $this->countryManager->getList();
    $elements['initial_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Default country'),
      '#options' => $countries,
      '#default_value' => $this->getSetting('initial_country'),
      '#description' => $this->t('Specify a default selection country. If geolocation is enabled this setting will be ignored.'),
      '#states' => [
        'invisible' => [
          '[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][geolocation]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $elements['preferred_countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Top list countries'),
      '#multiple' => TRUE,
      '#options' => $countries,
      '#default_value' => $this->getSetting('preferred_countries'),
      '#description' => $this->t('Specify the countries to appear at the top of the list. Leave it blank to follow an alphabetic order.'),
    ];

    $elements['countries'] = [
      '#type' => 'radios',
      '#title' => $this->t('List of countries'),
      '#options' => [
        'all' => $this->t('All'),
        'exclude' => $this->t('Exclude the selected countries'),
        'include' => $this->t('Include the selected countries'),
      ],
      '#default_value' => $this->getSetting('countries'),
    ];

    $elements['exclude_countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Countries'),
      '#title_display' => 'invisible',
      '#options' => $countries,
      '#multiple' => TRUE,
      '#default_value' => $this->getSetting('exclude_countries'),
      '#states' => [
        'invisible' => [
          '[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][countries]"]' => ['value' => 'all'],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $geolocation = (int) $this->getSetting('geolocation');
    $summary[] = $this->t('Use Geolocation: @display_label', ['@display_label' => ($geolocation ? $this->t('Yes') : 'No')]);

    if ($geolocation === 0 && ($this->getSetting('countries') === 'all')
      || (!empty($this->getSetting('exclude_countries')) && $this->getSetting('exclude_countries') === 'include' && in_array($this->getSetting('initial_country'), $this->getSetting('exclude_countries')))
      || (!empty($this->getSetting('exclude_countries')) && $this->getSetting('exclude_countries') === 'exclude' && !in_array($this->getSetting('initial_country'), $this->getSetting('exclude_countries')))
      ) {
      $summary[] = $this->t('Default country: @value', [
        '@value' => $this->getSetting('initial_country'),
      ]);
    }

    if (!empty($this->getSetting('preferred_countries'))) {
      $summary[] = $this->t('Top list countries: @value', [
        '@value' => implode(",", $this->getSetting('preferred_countries')),
      ]);
    }

    if ($this->getSetting('countries') === 'exclude' && !empty($this->getSetting('exclude_countries'))) {
      $summary[] = $this->t('Excluded countries: @value', [
        '@value' => implode(",", $this->getSetting('exclude_countries')),
      ]);
    }
    elseif ($this->getSetting('countries') === 'include' && !empty($this->getSetting('exclude_countries'))) {
      $summary[] = $this->t('Countries included: @value', [
        '@value' => implode(",", $this->getSetting('exclude_countries')),
      ]);
    }
    else {
      $summary[] = $this->t('Show all countries');
    }

    return $summary;
  }

}
