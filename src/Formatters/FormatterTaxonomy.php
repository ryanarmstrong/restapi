<?php

/**
 * @file
 * Contains \Drupal\restapi\Formatters\FormatterTaxonomy.
 */

namespace Drupal\restapi\Formatters;

use Drupal\restapi\FormatterInterface;

class FormatterTaxonomy extends FormatterField implements FormatterInterface {
  /**
   * Formats an entity field that is a taxonomy reference.
   *
   * @param object $entity
   *   The entity to ge the property from.
   * @param string $key
   *   The key value of the property to load.
   *
   * @return array
   *   Returns an array that has the term name, description, and weight.
   */
  public function format($entity, $key) {
    // Call the parent format() which grabs the value via Enity API.
    $value = parent::format($entity, $key);

    $terms = array();
    foreach ($value as $term) {
      $terms[$term->tid]['name'] = $term->name;
      $terms[$term->tid]['description'] = $term->description;
      $terms[$term->tid]['order'] = $term->weight;
    }
    return $terms;
  }
}
