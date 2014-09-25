<?php
/**
 * @file
 * rest.inc
 */

namespace Drupal\restapi\RestServices;

use Drupal\restapi\RestServiceInterface;
use Drupal\restapi\RestServices\BaseRestService;

/**
 * Provides a base RESTful service class.
 */
class NodeRestService extends BaseRestService implements RestServiceInterface {
  /**
   * Sets the filters for the query.
   */
  protected function setRequirements() {
    // Set the entity bundle.
    $this->query->condition('node.type', $this->route['requirements']['bundle']);

    // Set any defined entity property requirements.
    foreach ($this->route['requirements']['properties'] as $property => $value) {
      $this->query->condition('node.' . $property, $value);
    }
  }
}
