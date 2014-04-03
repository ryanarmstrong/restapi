<?php

/**
 * @file
 * Contains \Drupal\restapi\FilterInterface.
 */

namespace Drupal\restapi;

class SorterDefault implements interface SorterInterface {
  /**
   * Sort an query.
   *
   * @return object
   *   The modified query object.
   */
  public function sortValue();
}
