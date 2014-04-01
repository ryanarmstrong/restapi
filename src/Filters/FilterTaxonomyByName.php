<?php

/**
 * @file
 * Contains \Drupal\restapi\FilterInterface.
 */

namespace Drupal\restapi\Filters;

use Drupal\restapi\FilterInterface;

class FilterTaxonomyByName implements FilterInterface {
  /**
   * Filter an query.
   *
   * @return object
   *   The modified query object.
   */
  public function filterQuery($query, $filter, $value, $type) {
    // Figure out the vocabulary
    $field_info = field_info_field($filter['field']);
    $vocabulary = $field_info['settings']['allowed_values']['0']['vocabulary'];

    // Convert the filter name into a term id.
    $term = taxonomy_get_term_by_name($value, $vocabulary);
    $value = array_shift(array_values($term));

    // Modify the query.
    if (isset($filter['field'])) {
      $query->join('field_data_' . $filter['field'], $filter['field'], $filter['field'] . '.entity_id = ' . $type . '.nid');
      $query->condition($filter['field'] . '.' . $filter['value'], $value->tid);
    }

    return $query;
  }
}
