<?php

/**
 * @file
 * Contains \Drupal\restapi\Formatters\FormatterProperty.
 *
 * Certain contrib-added properties don't support Entity API.
 * This Formatter handles those properties.
 */

namespace Drupal\restapi\Formatters;

use Drupal\restapi\FormatterInterface;
use Drupal\restapi\Formatters\FormatterBase;

class FormatterProperty extends FormatterBase implements FormatterInterface {
  /**
   * FormatterProperty contructor.
   *
   * @param string $route_id
   *   The ID of the route.
   * @param array $variables
   *   The variables available to the RestService.
   */
  public function __construct($entity, $entity_type, $key) {
    $this->value = $entity->$key;
    // Handle empty value instances.
    if (empty($this->value)) {
      $this->status = NULL;
    }
  }

  /**
   * Formats an entity property such as title.
   *
   * @return string
   *   Simply returns the value.
   */
  public function format() {
    return $this->value;
  }
}
