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
  public function filterQuery($query, $filter, $values, $type) {
    // Convert the string into an array.
    $values_raw = explode(',', $values);
    $values = array();
    $operator = 'IN';

    // Check if the filter is a entity property filter.
    if (isset($filter['property'])) {
      $query->condition($type . '.' . $filter['property'], $values, $operator);
    }

    // Check if the filter is a entity field filter.
    if (isset($filter['field'])) {
      // Grab the field info so we know where to look.
      $field_info = field_info_field($filter['field']);

      foreach ($values_raw as $value) {
        // Check for a NOT condition.
        if (strpos($value, '!') === 0) {
          // If field contains multiple values, this must be handled later.
          if ($field_info['cardinality'] != 1) {
            $variables['multiple_field_not_filter'] = $filter;
            return $variables;
          }

          // Remove the ! from the $value so it can be used in the filter.
          $values[] = substr($value, 1);

          // Set the operator to NOT IN.
          $operator = 'NOT IN';
        }
        $values[] = $value;
      }
      $field_table = key(reset($field_info['storage']['details']['sql']));
      $field_column = $field_info['storage']['details']['sql']['FIELD_LOAD_CURRENT'][$field_table][$filter['value']];

      // Join the field table if it hasn't already been joined.
      if (empty($query->tables[$field_table])) {
        $query->join($field_table, $field_table, $field_table . '.entity_id = ' . $type . '.nid');
      }

      // Add the filter condition to the query.
      $query->condition($field_table . '.' . $field_column, $values, $operator);
    }

    $variables['query'] = $query;

    return $variables;
  }

  /**
   * Filter the entity IDs after the query has been run.
   *
   * @return array
   *   The modified entity ID list.
   */
  public function filterPostQuery($etids, $filter) {
    return $etids;
  }
}
