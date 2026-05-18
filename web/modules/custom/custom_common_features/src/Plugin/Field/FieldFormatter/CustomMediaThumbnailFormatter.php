<?php

namespace Drupal\custom_common_features\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\media_responsive_thumbnail\Plugin\Field\FieldFormatter\MediaResponsiveThumbnailFormatter;

/**
 * Plugin implementation of the 'media_thumbnail' formatter.
 *
 * @FieldFormatter(
 *   id = "custom_media_external_video_responsive_thumbnail",
 *   label = @Translation("Custom responsive thumbnail (only external videos)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class CustomMediaThumbnailFormatter extends MediaResponsiveThumbnailFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    $element['image_link']['#options']['external_media'] = $this->t('External media');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = parent::viewElements($items, $langcode);
    if ($elements && $items instanceof EntityReferenceFieldItemListInterface) {
      $media_items = $this->getEntitiesToView($items, $langcode);
      /** @var \Drupal\media\MediaInterface[] $media_items */
      foreach ($media_items as $delta => $media) {
        $item        = $elements[$delta]['#item']->getValue();
        $item['alt'] = $media->getName();
        $elements[$delta]['#item']->setValue($item);
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaThumbnailUrl(MediaInterface $media, EntityInterface $entity, $langcode): ?Url {
    $image_link_setting = $this->getSetting('image_link');
    if ($image_link_setting === 'external_media' && $media->hasField('field_media_oembed_video')) {
      $url = Url::fromUri($media->get('field_media_oembed_video')->getString());
    }
    else {
      $url = parent::getMediaThumbnailUrl($media, $entity, $langcode);
    }
    return $url;
  }

}
