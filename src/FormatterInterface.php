<?php

/**
 * @file
 * Contains \Drupal\restapi\FormatterInterface.
 */

namespace Drupal\restapi;

interface FormatterInterface {
  /**
   * Format an entity.
   *
   * @return string
   *   The formatted property or field.
   */
  public function format($entity, $entity_type, $key);
}
