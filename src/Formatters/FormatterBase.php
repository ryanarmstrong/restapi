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
  public $formatted_value;

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
    // Handle variable casting.
    $type = $this->wrapper->$key->type();
    switch ($type) {
      case 'date':
        $type = 'integer';
        break;
    }
    settype($this->value, $type);
  }

  /**
   * Formats an entity property such as title.
   *
   * @return string
   *   Simply returns the value.
   */
  public function format() {
    $this->formatted_value = !empty($this->value) ? $this->value : array();
    return $this->formatted_value;
  }
}
