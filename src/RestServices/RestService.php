<?php
/**
 * @file
 * rest.inc
 */

namespace Drupal\restapi\RestServices;

use Drupal\restapi\RestServiceInterface;
use Drupal\restapi\Formatters\FormatterProperty;
use Drupal\restapi\Formatters\FormatterField;
use Drupal\restapi\Formatters\FormatterTaxonomy;
use Drupal\restapi\YamlConfigDiscovery;

/**
 * Provides a base RESTful service class.
 */
class RestService implements RestServiceInterface {
  /**
   * The entity IDs of the requested resources.
   *
   * @var array
   */
  protected $etids;

  /**
   * The entity IDs of the requested resources.
   *
   * @var array
   */
  protected $filters = array();

  /**
   * The entity IDs of the requested resources.
   *
   * @var array
   */
  protected $mappings;

  /**
   * An array of options passed to the Service.
   *
   * - supported_requests
   *     The types of request methods that this service can support. Available
   *     options are GET, POST, PUT, DELETE, PATCH
   * - supported_formats
   *     The different formats that are supported, such as application/json.
   * - entity_type
   *     The type of entity that the service is requesting such as node, user,
   *     or taxonomy.
   * - entity_bundle
   *     The bundle type that the service is requesting. The node/content type
   *     or the vocabulary are common uses.
   *
   * @var array
   */
  protected $options;

  /**
   * The EntityFieldQuery used to return the requested resources.
   *
   * @var EntityFieldQuery object
   */
  protected $query;

  /**
   * The entities requested by the client, formatted as a response.
   *
   * @var array
   */
  protected $response;

  /**
   * The request from the client application.
   *
   * Stores the $_SERVER superglobal for use throughout the generation of the
   * response.
   *
   * @var array
   */
  protected $request;

  /**
   * The request from the client application.
   *
   * Stores the $_SERVER superglobal for use throughout the generation of the
   * response.
   *
   * @var array
   */
  protected $route;

  /**
   * RestService contructor.
   *
   * @param string $route_id
   *   The ID of the route.
   * @param int $etid
   *   Optional ID of a single entity to retrieve.
   */
  public function __construct($route_id, $etid) {
    $this->request = $_SERVER;
    $this->query = new \EntityFieldQuery();
    $this->etids = isset($etid) ? $etid : array();

    $config_discovery = new YamlConfigDiscovery();

    // Store the configuration for this route.
    $defined_routes = $config_discovery->parsedConfig('restapi.routing.yml');
    if (isset($defined_routes[$route_id])) {
      $this->route = $defined_routes[$route_id];
    }

    // Store the mapping for this route.
    $defined_mappings = $config_discovery->parsedConfig('restapi.mappings.yml');
    if (isset($defined_mappings[$this->route['mapping']])) {
      $this->mapping = $defined_mappings[$this->route['mapping']];
    }

    // Load defined filters and save the ones to use for this route.
    $defined_filters = $config_discovery->parsedConfig('restapi.filters.yml');
    foreach ($this->route['filters'] as $filter) {
      $this->filters[$filter] = isset($defined_filters[$filter]) ? $defined_filters[$filter] : NULL;
    }
  }

  /**
   * Generates a response to send to the client.
   * @return array
   *   Returns the response for the client.
   */
  public function generateResponse() {
    // First validate the response.
    $this->response = $validation = $this->validateRequest();
    if ($validation['status'] === 'ok') {
      // Retrieve the requested entities.
      $this->response = $this->retrieveEntities();
    }

    return $this->response;
  }

  /**
   * Validates the request.
   * @return array
   *   Returns the validated status of the request.
   */
  protected function validateRequest() {
    // Validate that the request method is supported.
    if (!in_array($this->request['REQUEST_METHOD'], $this->route['methods'])) {
      http_response_code(406);

      return array(
        'status' => 'unsupported_method',
        'message' => t('This service does not support the !method method.', array('!method' => $this->request['REQUEST_METHOD'])),
        'method' => $this->request['REQUEST_METHOD'],
      );
    }

    // Validate that the requested format is supported.
    if (!in_array($this->request['HTTP_ACCEPT'], $this->route['content_types'])) {
      http_response_code(406);

      return array(
        'status' => 'unsupported_content_type',
        'message' => t('This service does not support the @format format.', array('@format' => $this->request['HTTP_ACCEPT'])),
        'format' => $this->request['HTTP_ACCEPT'],
      );
    }

    return array(
      'status' => 'ok',
    );
  }

  /**
   * Retrieves the entities that pass through the given filters.
   * @return array
   *   Returns an array of formatted entities.
   */
  protected function retrieveEntities() {
    // If an entity ID is provided, format that entity.
    if (!empty($this->etids)) {
      // Format the entities returned.
      return $this->formatEntities();
    }

    // Set entity type and bundle.
    $this->query->entityCondition('entity_type', $this->mapping['entity_type']);
    if (isset($this->mapping['entity_bundle'])) {
      $this->query->entityCondition('bundle', $this->mapping['entity_bundle']);
    }

    // Set the requirements.
    $this->setRequirements();

    // Set the filters.
    if (!empty($this->filters)) {
      $this->setFilters();
    }

    // Run the query, load the entities, and format them.
    $results = $this->query->execute();

    if (isset($results[$this->mapping['entity_type']])) {
      $this->etids = array_keys($results[$this->mapping['entity_type']]);

      // Format the entities returned.
      return $this->formatEntities();
    }

    // Return an error response if no results were returned.
    return array(
      'status' => 'no_results',
      'message' => t('There are no entities that match the given conditions.'),
    );
  }

  /**
   * Sets the filters for the EntityFieldQuery.
   */
  protected function setRequirements() {
    // Set any defined entity property requirements.
    foreach ($this->route['requirements']['properties'] as $property => $value) {
      $this->query->propertyCondition($property, $value);
    }

    // Set any defined entity field requirements.
    foreach ($this->route['requirements']['fields'] as $field => $value) {
      $this->query->fieldCondition($field, $value);
    }
  }

  /**
   * Sets the filters for the EntityFieldQuery.
   */
  protected function setFilters() {
    // Load query string.
    $requested_filters = drupal_get_query_parameters();

    // Only procede if the client passed filters.
    if (!empty($requested_filters)) {
      foreach ($this->filters as $filter_name => $filter) {
        // Check if the filter is a entity property filter.
        if (isset($filter['property']) && isset($requested_filters[$filter_name])) {
          $this->query->propertyCondition($filter['property'], $requested_filters[$filter_name]);
        }
        // Check if the filter is a entity field filter.
        if (isset($filter['field']) && isset($requested_filters[$filter_name])) {
          $this->query->fieldCondition($filter['field'], $requested_filters[$filter_name]);
        }
      }
    }
  }

  /**
   * Formats a node into an array to return to the client app.
   * @return array
   *   A node formatted into an array.
   */
  protected function formatEntities() {
    // Load the entities.
    $unformatted_entities = entity_load($this->mapping['entity_type'], $this->etids);

    // If a mapping for this entity and entity bundle has been provide, use it.
    if (isset($this->mapping['data'])) {
      foreach ($unformatted_entities as $etid => $entity) {
        foreach ($this->mapping['data'] as $field_name => $map) {
          $formatter = new $map['formatter']();
          // Call the appropriete formatter.
          $formatted_entities[$etid][$map['label']] = $formatter->format($entity, $field_name);
        }
      }
      return $formatted_entities;
    }

    // Otherwise just return the unformatted entities.
    return $unformatted_entities;
  }
}
