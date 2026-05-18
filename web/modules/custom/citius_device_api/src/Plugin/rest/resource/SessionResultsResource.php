<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\rest\resource;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\citius_content\Event\ExerciseExtraDataEvent;
use Drupal\citius_content\Hook\SessionView;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Returns results of a session.
 */
#[RestResource(
  id: 'citius_device_api_session_results',
  label: new TranslatableMarkup('Session results'),
  uri_paths: [
    'canonical' => '/api/session-results/{id}',
  ],
)]
class SessionResultsResource extends ResourceBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Session view.
   *
   * @var \Drupal\citius_content\Hook\SessionView
   */
  protected SessionView $sessionView;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    /** @var \Drupal\citius_content\Hook\SessionView $session_view */
    $session_view = $container->get(SessionView::class);
    $instance->sessionView = $session_view;
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Responds to GET requests.
   *
   * @param int $id
   *   Session ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response.
   */
  public function get(int $id): ResourceResponse {
    $session = $this->entityTypeManager->getStorage('node')->load($id);
    if (!$session instanceof SessionNode) {
      throw new NotFoundHttpException();
    }
    $exercises = $session->getExercises();
    $response = [];
    foreach ($exercises as $exercise) {
      $response[] = $this->getExerciseResults($exercise, $session);
    }
    $cache = [
      '#cache' => [
        'tags' => ['execution_list'],
      ],
    ];
    $metadata = CacheableMetadata::createFromRenderArray($cache);
    return (new ResourceResponse($response))->addCacheableDependency($metadata);
  }

  /**
   * Get results for single exercise.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $exercise
   *   Exercise paragraph.
   * @param \Drupal\citius_content\Entity\SessionNode $session
   *   Session node.
   *
   * @return array
   *   Results.
   */
  protected function getExerciseResults(ParagraphInterface $exercise, SessionNode $session): array {
    $event = new ExerciseExtraDataEvent($exercise, $session);
    $this->eventDispatcher->dispatch($event, ExerciseExtraDataEvent::NAME);
    $data = $event->getData();
    $results = $data['results'] ?? [];
    if (empty($results)) {
      $output = [];
    }
    else {
      $output = [
        '#type' => 'html_tag',
        '#tag' => 'tr',
        '#attributes' => [
          'class' => ['session__exercise-results'],
          'data-exercise' => $exercise->id(),
        ],
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'td',
          '#attributes' => [
            'colspan' => 6,
          ],
          'value' => $this->sessionView->renderExerciseResults($results),
        ],
      ];
    }
    $result_column = $this->sessionView->renderResultsColumn($results);
    return [
      'exercise_id' => $exercise->id(),
      'markup' => $this->renderer->render($output),
      'result_column' => $this->renderer->render($result_column),
    ];
  }

}
