<?php

/**
 * @file
 * Contains \Drupal\restapi\FilterInterface.
 */

namespace Drupal\restapi\Filters;

use Drupal\restapi\FilterInterface;

abstract class FilterBase implements FilterInterface {
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
  abstract public function filterQuery(&$query);
}
