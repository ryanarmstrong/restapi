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
  public function filterQuery(&$query);

  /**
   * Filter the entity IDs after the query has been run.
   *
   * @return object
   *   The modified entity ID list.
   */
  public function filterPostQuery(&$etids);
}
