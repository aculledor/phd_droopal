<?php

declare(strict_types=1);

namespace Drupal\citius_device_api\Plugin\rest\resource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[RestResource(
  id: 'citius_device_api_glass_calibration',
  label: new TranslatableMarkup('Glass calibration'),
  uri_paths: [
    'create' => '/api/glass/calibration',
  ],
)]
final class GlassCalibrationResource extends ApiResourceBase {

  public function post(array $data): ModifiedResourceResponse {
    if (!$this->isAuthenticated()) {
      throw new AccessDeniedHttpException('Authentication required.');
    }

    $user_id = $data['metadata']['user_id'] ?? NULL;
    $routine_id = $data['metadata']['routine_id'] ?? NULL;
    $calibration_type = $data['calibration']['calibration_type'] ?? NULL;
    $height_meters = $data['calibration']['height_meters'] ?? NULL;
    $height_centimeters = $data['calibration']['height_centimeters'] ?? NULL;

    if (!$user_id || !$calibration_type || !is_numeric($height_meters)) {
      throw new BadRequestHttpException('Invalid calibration payload.');
    }

    if (!in_array($calibration_type, ['standing_height', 'squat_height'], TRUE)) {
      throw new BadRequestHttpException('Invalid calibration type.');
    }

    $height_meters = (float) $height_meters;
    $height_centimeters = is_numeric($height_centimeters)
      ? (float) $height_centimeters
      : $height_meters * 100.0;

    $height_centimeters_int = (int) round($height_centimeters);

    if ($calibration_type === 'standing_height' && ($height_centimeters_int < 80 || $height_centimeters_int > 230)) {
      throw new BadRequestHttpException('Invalid standing height value.');
    }

    if ($calibration_type === 'squat_height' && ($height_centimeters_int < 30 || $height_centimeters_int > 180)) {
      throw new BadRequestHttpException('Invalid squat height value.');
    }

    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load((int) $user_id);

    if (!$user) {
      throw new BadRequestHttpException('Invalid user id.');
    }

    $field_name = match ($calibration_type) {
      'standing_height' => 'field_height',
      'squat_height' => 'field_squat_height',
    };

    if (!$user->hasField($field_name)) {
      throw new BadRequestHttpException(sprintf('User does not have field %s.', $field_name));
    }

    $user->set($field_name, $height_centimeters_int);
    $user->save();

    \Drupal::logger('citius_device_api')->notice('Height calibration saved. user_id=@user_id routine_id=@routine_id type=@type field=@field height_cm=@height_cm', [
      '@user_id' => $user_id,
      '@routine_id' => $routine_id ?? '',
      '@type' => $calibration_type,
      '@field' => $field_name,
      '@height_cm' => $height_centimeters_int,
    ]);

    return new ModifiedResourceResponse([
      'status' => 'ok',
      'payload_type' => 'height_calibration',
      'user_id' => $user_id,
      'routine_id' => $routine_id,
      'calibration_type' => $calibration_type,
      'field' => $field_name,
      'height_meters' => $height_meters,
      'height_centimeters' => $height_centimeters_int,
      'saved' => TRUE,
    ], 201);
  }

}