<?php

/**
 * @file
 * Contains \Drupal\restapi\Formatters\FormatterProperty.
 */

namespace Drupal\restapi\Formatters;

use Drupal\restapi\FormatterInterface;

class FormatterProperty implements FormatterInterface {
  /**
   * Formats an entity property such as title.
   *
   * @param object $entity
   *   The entity to ge the property from.
   * @param string $key
   *   The key value of the property to load.
   *
   * @return string
   *   Simply returns the value.
   */
  public function format($entity, $key) {
    return $entity->$key;
  }
}
