<?php

/**
 * @file
 * Contains \Drupal\restapi\Formatters\FormatterTaxonomy.
 */

namespace Drupal\restapi\Formatters;

use Drupal\restapi\FormatterInterface;

class FormatterTaxonomy implements FormatterInterface {
  /**
   * Formats an entity field that is a taxonomy reference.
   *
   * @param object $entity
   *   The entity to ge the property from.
   * @param string $entity_type
   *   The type of entity.
   * @param string $key
   *   The key value of the property to load.
   *
   * @return array
   *   Returns an array that has the term name, description, and weight.
   */
  public function format($entity, $entity_type, $key) {
    $wrapper = entity_metadata_wrapper($entity_type, $entity);
    $value = $wrapper->$key->value();

    $terms = array();
    foreach ($value as $term) {
      $terms[$term->tid]['name'] = $term->name;
      $terms[$term->tid]['description'] = $term->description;
      $terms[$term->tid]['order'] = $term->weight;
    }
    return $terms;
  }
}
