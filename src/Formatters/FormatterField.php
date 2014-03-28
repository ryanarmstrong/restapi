<?php

/**
 * @file
 * Contains \Drupal\restapi\Formatters\FormatterField.
 */

namespace Drupal\restapi\Formatters;

use Drupal\restapi\FormatterInterface;

class FormatterField implements FormatterInterface {
  /**
   * Formats an entity property such as title.
   *
   * @param object $entity
   *   The entity to ge the property from.
   * @param string $entity_type
   *   The type of entity.
   * @param string $key
   *   The key value of the property to load.
   *
   * @return string
   *   Simply returns the value.
   */
  public function format($entity, $entity_type, $key) {
    $wrapper = entity_metadata_wrapper($entity_type, $entity);
    $value = $wrapper->$key->value();

    return $value;
  }
}
