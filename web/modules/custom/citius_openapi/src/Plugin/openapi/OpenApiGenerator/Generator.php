<?php

namespace Drupal\citius_openapi\Plugin\openapi\OpenApiGenerator;

use Drupal\openapi\Plugin\openapi\OpenApiGeneratorBase;

/**
 * Defines an OpenApi Schema Generator for CITIUS device API module.
 *
 * @OpenApiGenerator(
 *   id = "citius",
 *   label = @Translation("CITIUS"),
 * )
 */
class Generator extends OpenApiGeneratorBase {

  /**
   * {@inheritDoc}
   */
  public function getApiName(): string {
    return $this->t('CITIUS');
  }

  /**
   * {@inheritDoc}
   */
  protected function getJsonSchema($described_format, $entity_type_id, $bundle_name = NULL): array {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  protected function getApiDescription(): string {
    return $this->t('Endpoints to work with glasses devices.');
  }

  /**
   * {@inheritdoc}
   *
   * Provide manual OpenAPI definitions for the custom device API.
   */
  public function getDefinitions(): array {
    $definitions = [];

    $definitions['RegisterGlassesResponse'] = [
      'type' => 'object',
      'properties' => [
        'id' => [
          'type' => 'string',
          'example' => 'device-123',
        ],
        'secret' => [
          'type' => 'string',
          'example' => 's3cr3t',
        ],
      ],
    ];

    $definitions['AuthorizeGlassesRequest'] = [
      'type' => 'object',
      'required' => ['id', 'secret'],
      'properties' => [
        'id' => ['type' => 'string'],
        'secret' => ['type' => 'string'],
      ],
    ];

    $definitions['AuthorizeGlassesResponse'] = [
      'type' => 'object',
      'properties' => [
        'token' => ['type' => 'string'],
      ],
    ];

    // Normalized SessionNode items as produced by SessionNodeNormalizer.
    $definitions['SessionExerciseItem'] = [
      'type' => 'object',
      'properties' => [
        'exercise_id' => ['type' => 'integer', 'example' => 10],
        'duration' => ['type' => 'integer', 'example' => 60],
        'time_between_events' => ['type' => 'integer', 'example' => 2],
        'expected_responses' => ['type' => 'number', 'example' => 30],
      ],
    ];

    $definitions['SessionNodeNormalized'] = [
      'type' => 'object',
      'properties' => [
        'user_id' => ['type' => 'integer', 'example' => 123],
        'routine_id' => ['type' => 'integer', 'example' => 456],
        'exercises' => [
          'type' => 'array',
          'items' => ['$ref' => '#/definitions/SessionExerciseItem'],
        ],
      ],
    ];

    $definitions['GlassesListResponse'] = [
      'type' => 'object',
      'properties' => [
        'metadata' => [
          'type' => 'object',
          'properties' => [
            'version' => ['type' => 'string'],
            'timestamp' => ['type' => 'string'],
            'source' => ['type' => 'string'],
          ],
        ],
        'unity_session_routines' => [
          'type' => 'array',
          'items' => ['$ref' => '#/definitions/SessionNodeNormalized'],
        ],
      ],
    ];

    $definitions['ExerciseSubmissionRequest'] = [
      'type' => 'object',
      'required' => ['metadata', 'exercise_event', 'movement_data'],
      'properties' => [
        'metadata' => [
          'type' => 'object',
          'required' => ['routine_id', 'user_id'],
          'properties' => [
            'version' => ['type' => 'string', 'example' => '1.0'],
            'timestamp' => ['type' => 'string', 'example' => '2025-01-01T12:00:00Z'],
            'source' => ['type' => 'string', 'example' => 'unity-client'],
            'routine_id' => ['type' => 'string', 'example' => '123'],
            'user_id' => ['type' => 'string', 'example' => '456'],
          ],
        ],
        'exercise_event' => [
          'type' => 'object',
          'required' => ['exercise_id', 'outcome', 'timestamp'],
          'properties' => [
            'event_type' => ['type' => 'string', 'example' => 'execution'],
            'event_id' => ['type' => 'string', 'example' => 'evt-1'],
            'exercise_id' => ['type' => 'string', 'example' => '10'],
            'outcome' => ['type' => 'string', 'example' => 'success'],
            'timestamp' => ['type' => 'string', 'example' => '31/12/2024 13:45'],
          ],
        ],
        'movement_data' => [
          'type' => 'object',
          'required' => [
            'left_controller_x', 'left_controller_y', 'left_controller_z',
            'right_controller_x', 'right_controller_y', 'right_controller_z',
            'head_x', 'head_y', 'head_z',
          ],
          'properties' => [
            'left_controller_x' => ['type' => 'number', 'format' => 'float', 'example' => 0.12],
            'left_controller_y' => ['type' => 'number', 'format' => 'float', 'example' => -0.05],
            'left_controller_z' => ['type' => 'number', 'format' => 'float', 'example' => 1.23],
            'right_controller_x' => ['type' => 'number', 'format' => 'float', 'example' => 0.22],
            'right_controller_y' => ['type' => 'number', 'format' => 'float', 'example' => -0.15],
            'right_controller_z' => ['type' => 'number', 'format' => 'float', 'example' => 1.13],
            'head_x' => ['type' => 'number', 'format' => 'float', 'example' => 0.01],
            'head_y' => ['type' => 'number', 'format' => 'float', 'example' => 0.02],
            'head_z' => ['type' => 'number', 'format' => 'float', 'example' => -0.98],
          ],
        ],
      ],
    ];

    return $definitions;
  }

  /**
   * {@inheritDoc}
   */
  public function getSecurityDefinitions(): array {
    $definitions = parent::getSecurityDefinitions();
    $definitions['bearerAuth'] = [
      'type' => 'apiKey',
      'name' => 'Authorization',
      'in' => 'header',
    ];
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * Provide manual OpenAPI paths for the custom device API.
   */
  public function getPaths(): array {
    $paths = [];

    $paths['/api/glass/register'] = [
      'post' => [
        'tags' => ['citius-device'],
        'summary' => $this->t('Register glasses'),
        'responses' => [
          '201' => [
            'description' => 'Created',
            'schema' => ['$ref' => '#/definitions/RegisterGlassesResponse'],
          ],
        ],
        'consumes' => ['application/json'],
        'produces' => ['application/json'],
        'operationId' => 'registerGlasses',
      ],
    ];

    $paths['/api/glass/authorize'] = [
      'post' => [
        'tags' => ['citius-device'],
        'summary' => $this->t('Authorize glasses'),
        'parameters' => [
          [
            'name' => 'body',
            'in' => 'body',
            'required' => TRUE,
            'schema' => ['$ref' => '#/definitions/AuthorizeGlassesRequest'],
          ],
        ],
        'responses' => [
          '201' => [
            'description' => 'Created',
            'schema' => ['$ref' => '#/definitions/AuthorizeGlassesResponse'],
          ],
          '400' => [
            'description' => 'Bad request',
          ],
        ],
        'consumes' => ['application/json'],
        'produces' => ['application/json'],
        'operationId' => 'authorizeGlasses',
      ],
    ];

    $paths['/api/glass'] = [
      'get' => [
        'tags' => ['citius-device'],
        'summary' => $this->t('Get glasses sessions'),
        'parameters' => [
          [
            'name' => 'id',
            'in' => 'query',
            'required' => TRUE,
            'type' => 'string',
            'description' => $this->t('Device id'),
          ],
        ],
        'responses' => [
          '200' => [
            'description' => 'successful operation',
            'schema' => ['$ref' => '#/definitions/GlassesListResponse'],
          ],
          '400' => ['description' => 'Bad request'],
          '401' => ['description' => 'Unauthorized'],
        ],
        'produces' => ['application/json'],
        'operationId' => 'getGlassesSessions',
      ],
    ];

    $paths['/api/exercise'] = [
      'post' => [
        'tags' => ['citius-device'],
        'summary' => $this->t('Submit exercise execution'),
        'parameters' => [
          [
            'name' => 'body',
            'in' => 'body',
            'required' => TRUE,
            'schema' => ['$ref' => '#/definitions/ExerciseSubmissionRequest'],
          ],
        ],
        'responses' => [
          '201' => [
            'description' => 'Created',
            'schema' => ['$ref' => '#/definitions/ExerciseSubmissionRequest'],
          ],
          '400' => ['description' => 'Bad request'],
          '401' => ['description' => 'Unauthorized'],
        ],
        'consumes' => ['application/json'],
        'produces' => ['application/json'],
        'operationId' => 'postExerciseExecution',
      ],
    ];

    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumes(): array {
    return ['application/json'];
  }

  /**
   * {@inheritdoc}
   */
  public function getProduces(): array {
    return ['application/json'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTags(): array {
    return [
      [
        'name' => 'citius-device',
        'description' => $this->t('CITIUS device API endpoints'),
      ],
    ];
  }

}
