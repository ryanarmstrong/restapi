<?php

/**
 * @file
 * Contains \Drupal\restapi\Formatters\FormatterBase.
 */

namespace Drupal\restapi\Formatters;

use Drupal\restapi\FormatterInterface;

class FormatterBase implements FormatterInterface {
  /**
   * The Entity API wrapper for the Entity.
   *
   * @var array
   */
  protected $wrapper;

  /**
   * The Entity value requested, unformatted.
   *
   * @var array
   */
  protected $value;

  /**
   * The Entity value requested, formatted.
   *
   * @var array
   */
  public $formatted_value;

  /**
   * The status of the formatter after running any validation.
   *
   * @var boolean
   */
  public $status = TRUE;

  /**
   * FormatterBase contructor.
   *
   * @param string $route_id
   *   The ID of the route.
   * @param array $variables
   *   The variables available to the RestService.
   */
  public function __construct($entity, $entity_type, $key) {
    $this->wrapper = entity_metadata_wrapper($entity_type, $entity);
    $this->value = $this->wrapper->$key->value();
    // Handle empty value instances.
    if (empty($this->value)) {
      $this->status = NULL;
    }
  }

  /**
   * Formats an entity property such as title.
   *
   * @param object $entity
   *   The entity to ge the property from.
   * @param string $entity_type
   *   The type of entity.
   * @param string $key
   *   The key value of the property to load.
   *
   * @return string
   *   Simply returns the value.
   */
  public function format() {
    return $this->value;
  }
}
