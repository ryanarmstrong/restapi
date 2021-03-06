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
   * @param EntityObject $entity
   *   The entity to get the properties from.
   * @param string $entity_type
   *   The entity type.
   * @param string $key
   *   The property key to return.
   */
  public function __construct($entity, $entity_type, $key, $variables) {
    $this->variables = $variables;
    $this->value = $entity->$key;
  }

  /**
   * Formats an entity property such as title.
   *
   * @return string
   *   Simply returns the value.
   */
  public function format() {
    $this->formatted_value = !empty($this->value) ? $this->value : restapi_get_empty('integer', 1);
    return $this->formatted_value;
  }
}
