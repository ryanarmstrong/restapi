<?php

/**
 * @file
 * Contains \Drupal\restapi\FilterInterface.
 */

namespace Drupal\restapi\Filters;

use Drupal\restapi\FilterInterface;

class FilterDefault implements FilterInterface {
  /**
   * Filter an query.
   *
   * @return object
   *   The modified query object.
   */
  public function filterQuery($query, $filter, $value, $type) {
    // Check if the filter is a entity property filter.
    if (isset($filter['property'])) {
      $query->condition($type . '.' . $filter['property'], $value);
    }
    // Check if the filter is a entity field filter.
    if (isset($filter['field'])) {
      $query->join('field_data_' . $filter['field'], $filter['field'], $filter['field'] . '.entity_id = ' . $type . '.nid');
      $query->condition($filter['field'] . '.' . $filter['value'], $value);
    }

    return $query;
  }
}
