<?php

/**
 * @file
 * Contains \Drupal\restapi\FilterInterface.
 */

namespace Drupal\restapi\Filters;

use Drupal\restapi\FilterInterface;

class FilterBase implements FilterInterface {
  /**
   * The filter being used.
   *
   * @var array
   */
  public $filter;

  /**
   * The values that were passed.
   *
   * @var array
   */
  public $value;
  /**
   * FilterBase contructor.
   *
   * @param QueryObject $query
   *   The query to filter.
   * @param array $variables
   *   The variables available to the RestService.
   */
  public function __construct($filter_definition) {
    $this->filter = $filter_definition;
    $this->value = $filter_definition['value'];
  }

  /**
   * Filter an query.
   *
   * @return object
   *   The modified query object.
   */
  public function filterQuery(&$query) {
    // TO-DO
  }

  /**
   * Filter the entity IDs after the query has been run.
   *
   * @return array
   *   The modified entity ID list.
   */
  public function filterPostQuery(&$etids) {
    // TO-DO
  }
}
