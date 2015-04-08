<?php

/**
 * @file
 * Contains \Drupal\restapi\Filters\Filter.
 */

namespace Drupal\restapi\Filters;

use Drupal\restapi\FilterInterface;

class Filter implements FilterInterface {
  /**
   * The entity being filtered on.
   *
   * @var array
   */
   protected $entity_info;

  /**
   * The filter being used.
   *
   * @var array
   */
   protected $filter;

  /**
   * The route calling the filter.
   *
   * @var array
   */
   protected $route;

  /**
   * The values that were passed.
   *
   * @var array
   */
  protected $value;

  /**
   * Filter contructor.
   */
  public function __construct($variables) {
    $this->filter = $variables;
    $this->value = $this->filter['value'];
    $this->route = $this->filter['route'];
    $this->entity_info = $this->filter['entity_info'];
    $this->base_table_join = $this->entity_info['base table'] . '.' . $this->entity_info['entity keys']['id'];
  }

  /**
   * Filter an query.
   */
  public function filterQuery(&$query, $variables) {}
}
