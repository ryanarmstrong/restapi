<?php

/**
 * @file
 * Contains \Drupal\restapi\FilterInterface.
 */

namespace Drupal\restapi;

interface FilterInterface {
  /**
   * Filter an query.
   *
   * @return object
   *   The modified query object.
   */
  public function filterQuery($query, $filter, $value, $type);
}
