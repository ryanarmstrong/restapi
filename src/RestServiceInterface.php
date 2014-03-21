<?php

/**
 * @file
 * Contains \Drupal\restapi\FormatterInterface.
 */

namespace Drupal\restapi;

interface RestServiceInterface {
  /**
   * Generates a response to send to the client.
   * @return array
   *   Returns the response for the client.
   */
  public function generateResponse();
}
