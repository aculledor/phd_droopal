<?php

namespace Drupal\json_field\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a JSON pretty render element.
 *
 * @RenderElement("json_pretty")
 */
class JsonPretty extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#json' => NULL,
      '#pre_render' => [[static::class, 'preRenderJsonPretty']],
    ];
  }

  /**
   * Pre-render callback: Renders a JSON text element into #markup.
   */
  public static function preRenderJsonPretty(array $element): array {
    return [
      '#markup' => new FormattableMarkup(
        '<div class="json-field-pretty">' . static::formatJson($element['#json']) . '</div>', [],
      ),
      '#attached' => ['library' => ['json_field/json_field.pretty']],
    ];
  }

  /**
   * Formats using nested HTML lists.
   *
   * @param mixed $json
   *   The JSON data to display.
   *
   * @return string
   *   The JSON data formatted as nested HTML lists.
   */
  protected static function formatJson($json): string {
    $markup = '';
    if (is_array($json)) {
      $markup .= '<ul>';
      foreach ($json as $value) {
        $markup .= '<li>' . static::formatJson($value) . '</li>';
      }
      $markup .= '</ul>';
    }
    elseif (is_object($json)) {
      $markup .= '<dl>';
      foreach ($json as $key => $value) {
        $markup .= '<dt>' . $key . '</dt><dd>' . static::formatJson($value) . '</dd>';
      }
      $markup .= '</dl>';
    }
    elseif (is_null($json)) {
      $markup .= 'null';
    }
    elseif (is_bool($json)) {
      $markup .= $json ? 'true' : 'false';
    }
    else {
      $markup .= $json;
    }
    return $markup;
  }

}
