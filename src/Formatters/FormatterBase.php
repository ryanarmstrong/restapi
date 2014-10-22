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
   * The field info.
   *
   * @var object
   */
  protected $field_info;

  /**
   * The field type.
   *
   * @var string
   */
  protected $type;

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
   * An array of variables passed by the request.
   *
   * @var array
   */
  public $variables;

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
  public function __construct($entity, $entity_type, $key, $variables) {
    $this->variables = $variables;
    $this->entity = $entity;
    $this->wrapper = entity_metadata_wrapper($entity_type, $entity);
    $this->value = $this->wrapper->$key->value();
    $this->field_info = field_info_field($key);
    // Handle variable casting.
    $this->type = $this->wrapper->$key->type();
    switch ($this->type) {
      case 'date':
        $this->type = 'integer';
        break;
    }
    settype($this->value, $this->type);
  }

  /**
   * Formats an entity property such as title.
   *
   * @return string
   *   Simply returns the value.
   */
  public function format() {
    $this->formatted_value = isset($this->value) ? $this->value : restapi_get_empty($this->type, $this->field_info['cardinality']);
    return $this->formatted_value;
  }
}
