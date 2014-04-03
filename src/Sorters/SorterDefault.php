<?php

/**
 * @file
 * Contains \Drupal\restapi\FilterInterface.
 */

namespace Drupal\restapi\Sorters;

use Drupal\restapi\SorterInterface;

interface SorterInterface {
  /**
   * Get the orderBy value.
   *
   * @return object
   *   The modified query object.
   */
  public function sortValue($sorter) {
    return $value;
  }
}
