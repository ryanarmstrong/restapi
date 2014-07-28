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
   * The entity.
   *
   * @var object
   */
  protected $entity;

  /**
   * The value requested, unformatted.
   *
   * @var mixed
   */
  protected $value;

  /**
   * The value requested, formatted.
   *
   * @var mixed
   */
  public $this->formatted_value;

  /**
   * The status of the formatter after running any validation.
   *
   * @var boolean
   */
  public $status = TRUE;

  /**
   * FormatterBase contructor.
   *
   * @param EntityObject $entity
   *   The entity being formatted.
   * @param string $entity_type
   *   The entity being formatted.
   * @param string $key
   *   The key of the field or property to format.
   */
  public function __construct($entity, $entity_type, $key) {
    $this->entity = $entity;
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
   * @return string
   *   Simply returns the value.
   */
  public function format() {
    $this->formatted_value = array();
    $this->formatted_value = $this->value;
    return $this->formatted_value;
  }
}
