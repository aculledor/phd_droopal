<?php

namespace Drupal\citius_device_api\Normalizer;

use Drupal\citius_content\Entity\SessionNode;
use Drupal\citius_content\NodeBundles;
use Drupal\citius_content\NodeFields;
use Drupal\citius_content\ParagraphBundles;
use Drupal\citius_content\ParagraphFields;
use Drupal\citius_content\TaxonomyFields;
use Drupal\taxonomy\TermInterface;
use Drupal\citius_user\UserFields;
use Drupal\node\NodeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for SessionNode entities.
 */
class SessionNodeNormalizer implements NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = []): array {
    /** @var \Drupal\citius_content\Entity\SessionNode $session */
    $session = $data;
    $response = [
      'user_id' => (int) $session->get(NodeFields::PATIENT)->target_id,
      'routine_id' => (int) $session->id(),
    ];
    $patient = $session->get(NodeFields::PATIENT)->entity;
    $response['height'] = (int) ($patient?->get(UserFields::HEIGHT)->value ?? 0);
    $routine = $session->get(NodeFields::ROUTINE)->entity;
    if ($routine instanceof NodeInterface && $routine->bundle() === NodeBundles::ROUTINE) {
      $exercises = $routine->get(NodeFields::EXERCISES)->referencedEntities();
      $exercise_data = [];
      /** @var \Drupal\paragraphs\ParagraphInterface $exercise */
      foreach ($exercises as $exercise) {
        if ($exercise->bundle() !== ParagraphBundles::EXERCISE) {
          continue;
        }
        $duration = (int) ($exercise->get(ParagraphFields::DURATION)->value ?? 0);
        $intensity = (int) ($exercise->get(ParagraphFields::INTENSITY)->value ?? 1);
        $responses = $intensity !== 0 ? $duration / $intensity : 0;
        $exercise_node = $exercise->get(ParagraphFields::EXERCISE)->entity;
        $exercise_name = '';
        $exercise_type = '';
        $exercise_type_code = '';
        if ($exercise_node instanceof NodeInterface && $exercise_node->bundle() === NodeBundles::EXERCISE) {
          $exercise_name = $exercise_node->label();
          $exercise_type_term = $exercise_node->get(NodeFields::TYPE)->entity;
          if ($exercise_type_term instanceof TermInterface) {
            $exercise_type = $exercise_type_term->label();
            $exercise_type_code = (string) ($exercise_type_term->get(TaxonomyFields::CODE)->value ?? '');
          }
        }
        $exercise_data[] = [
          'exercise_id' => (int) $exercise->id(),
          'exercise_name' => $exercise_name,
          'exercise_type' => $exercise_type,
          'exercise_type_code' => $exercise_type_code,
          'duration' => $duration,
          'time_between_events' => $intensity,
          'expected_responses' => $responses,
          'height' => (int) ($exercise->get(ParagraphFields::HEIGHT)->value ?? 0),
          'distance' => (int) ($exercise->get(ParagraphFields::DISTANCE)->value ?? 0),
        ];
      }
      $response['exercises'] = $exercise_data;
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL, array $context = []): bool {
    return $data instanceof SessionNode;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      SessionNode::class => TRUE,
    ];
  }

}
