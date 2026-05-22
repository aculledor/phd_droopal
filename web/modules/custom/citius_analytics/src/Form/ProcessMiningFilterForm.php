<?php

declare(strict_types=1);

namespace Drupal\citius_analytics\Form;

use Drupal\citius_analytics\FilterOptionsRepository;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ProcessMiningFilterForm extends FormBase {

  public function __construct(
    protected FilterOptionsRepository $filterOptionsRepository,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get(FilterOptionsRepository::class),
    );
  }

  public function getFormId(): string {
    return 'citius_process_mining_filter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $request = $this->getRequest();

    $form['#method'] = 'get';
    $form['#attributes']['class'][] = 'citius-process-mining-form';
    $form['#attributes']['class'][] = 'citius-analytics-filters';

    $form['patient'] = [
      '#type' => 'select',
      '#title' => $this->t('Paciente'),
      '#options' => $this->filterOptionsRepository->getPatientOptions(),
      '#empty_option' => $this->t('- Seleccionar -'),
      '#default_value' => $request->query->get('patient') ?: NULL,
      '#wrapper_attributes' => [
        'class' => ['filter-item', 'filter-item--patient'],
      ],
    ];

    $length_options = array_combine(range(3, 10), range(3, 10));

    $form['subtrace_length'] = [
      '#type' => 'select',
      '#title' => $this->t('Número de elementos en la subtraza'),
      '#options' => $length_options,
      '#empty_option' => $this->t('- Seleccionar -'),
      '#default_value' => $request->query->get('subtrace_length') ?: NULL,
      '#wrapper_attributes' => [
        'class' => ['filter-item', 'filter-item--length'],
      ],
    ];

    $form['intensity'] = [
      '#type' => 'select',
      '#title' => $this->t('Intensidad'),
      '#options' => $this->filterOptionsRepository->getIntensityOptions(),
      '#empty_option' => $this->t('- Seleccionar -'),
      '#default_value' => $request->query->get('intensity') ?: NULL,
      '#wrapper_attributes' => [
        'class' => ['filter-item', 'filter-item--intensity'],
      ],
    ];

    $form['date_start'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha desde'),
      '#default_value' => $request->query->get('date_start') ?: '',
      '#wrapper_attributes' => [
        'class' => ['filter-item', 'filter-item--date-start'],
      ],
    ];

    $form['date_end'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha hasta'),
      '#default_value' => $request->query->get('date_end') ?: '',
      '#wrapper_attributes' => [
        'class' => ['filter-item', 'filter-item--date-end'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['filter-item', 'filter-item--actions'],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Buscar'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    $query = [];

    foreach (['patient', 'subtrace_length', 'intensity', 'date_start', 'date_end'] as $key) {
      if (!empty($values[$key])) {
        $query[$key] = $values[$key];
      }
    }

    $form_state->setRedirect('<current>', [], [
      'query' => $query,
    ]);
  }

}
