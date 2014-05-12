<?php
/**
 * @file
 * rest.inc
 */

namespace Drupal\restapi\RestServices;

use Drupal\restapi\RestServiceInterface;
use Drupal\restapi\Filters\FilterDefault;
use Drupal\restapi\Formatters\FormatterProperty;
use Drupal\restapi\Formatters\FormatterField;
use Drupal\restapi\YamlConfigDiscovery;

/**
 * Provides a base RESTful service class.
 */
class RestService implements RestServiceInterface {
  /**
   * The request from the client application.
   *
   * Stores the $_SERVER superglobal for use throughout the generation of the
   * response.
   *
   * @var array
   */
  protected $entity_identifier;

  /**
   * The entity IDs of the requested resources.
   *
   * @var array
   */
  protected $etids;

  /**
   * The filters supported by the service.
   *
   * @var array
   */
  protected $filters = array();

  /**
   * The sorters mappers used by the service.
   *
   * @var array
   */
  protected $mappings;

  /**
   * Filters with NOT conditions on a field with multiple values must be handled
   * seperately. This contains any that need to be run.
   *
   * @var array
   */
  protected $postQueryFilters = array();

  /**
   * The EntityFieldQuery used to return the requested resources.
   *
   * @var EntityFieldQuery object
   */
  public $query;

  /**
   * The request from the client application.
   *
   * Stores the $_SERVER superglobal for use throughout the generation of the
   * response.
   *
   * @var array
   */
  protected $query_parameters;

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
   * The sorters supported by the service.
   *
   * @var array
   */
  protected $sorters;

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
  public $variables;

  /**
   * RestService contructor.
   *
   * @param string $route_id
   *   The ID of the route.
   * @param array $variables
   *   The variables available to the RestService.
   */
  public function __construct($route_id, $variables) {
    $this->request = $_SERVER;
    $this->variables = $variables;
    $this->etids = isset($variables['etid']) ? array($variables['etid']) : array();

    // Load query string.
    $this->query_parameters = drupal_get_query_parameters();

    $config_discovery = new YamlConfigDiscovery();

    // Store the configuration for this route.
    $defined_routes = $config_discovery->parsedConfig('restapi.routing.yml');
    if (isset($defined_routes[$route_id])) {
      $this->route = $defined_routes[$route_id];
    }

    // Set the entity identifier.
    $entity_info = entity_get_info($this->route['requirements']['type']);
    $this->entity_identifier = $entity_info['entity keys']['id'];

    // Setup the query object.
    $this->query = db_select($this->route['requirements']['type'], $this->route['requirements']['type']);
    $this->query->fields($this->route['requirements']['type'], array($this->entity_identifier));

    // Store the mapping for this route.
    $defined_mappings = $config_discovery->parsedConfig('restapi.mappings.yml');
    // Load the default mapper defined by the route.
    if (isset($defined_mappings[$this->route['defaults']['mapping']])) {
      $this->mappings = $defined_mappings[$this->route['defaults']['mapping']];
    }
    // Override the route default with a mapper provided by the caller.
    if (isset($this->variables['mapping'])) {
      $this->mappings = $defined_mappings[$this->variables['mapping']];
    }
    // Override the caller mapper with a mapper provided by the client request.
    if (isset($this->query_parameters['mapping'])) {
      $this->mappings = $defined_mappings[$this->query_parameters['mapping']];
    }

    // Load defined filters and save the ones to use for this route.
    $defined_filters = $config_discovery->parsedConfig('restapi.filters.yml');
    if (isset($defined_filters[$this->route['defaults']['filters']])) {
      $this->filters = $defined_filters[$this->route['defaults']['filters']];
    }

    // Store the sorters for this route.
    $defined_sorters = $config_discovery->parsedConfig('restapi.sorters.yml');
    if (isset($defined_sorters[$this->route['defaults']['sorters']])) {
      $this->sorters = $defined_sorters[$this->route['defaults']['sorters']];
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

    // Set the requirements.
    $this->setRequirements();

    // Only procede if the client passed filters.
    if (!empty($this->query_parameters)) {
      // Run the query filters.
      $this->setQueryFilters();
    }

    // Set the filters.
    $this->setSorters();

    // Run the query, load the entities, and format them.
    $results = $this->query->execute();
    $entity_identifier = $this->entity_identifier;
    foreach ($results as $value) {
      $this->etids[$value->$entity_identifier] = $value->$entity_identifier;
    }

    // Run the post-query filters.
    if (!empty($this->postQueryFilters)) {
      $this->setPostQueryFilters();
    }

    if (!empty($this->etids)) {
      return $this->formatEntities();
    }

    // Return an error response if no results were returned.
    http_response_code(204);
    return array(
      'status' => 'no_results',
      'message' => t('There are no entities that match the given conditions.'),
    );
  }

  /**
   * Sets the filters for the query.
   */
  protected function setRequirements() {
    // Set the entity bundle.
    $this->query->condition($this->route['requirements']['type'] . '.type', $this->route['requirements']['bundle']);

    // Set any defined entity property requirements.
    foreach ($this->route['requirements']['properties'] as $property => $value) {
      $this->query->condition($this->route['requirements']['type'] . '.' . $property, $value);
    }

    // Call any custom callbacks.
    if (isset($this->route['requirements']['custom_callback'])) {
      call_user_func($this->route['requirements']['custom_callback'], array($this->variables, $this->query));
    }
  }

  /**
   * Sets the filters for the query.
   */
  protected function setQueryFilters() {
    // Loop through the passed query parameters.
    foreach ($this->query_parameters as $filter_name => $values) {
      // Check to make sure this is a supported filter and it isn't empty.
      if (array_key_exists($filter_name, $this->filters) && !empty($values)) {
        // Set the default filter. Can be overriden later.
        $filter = new FilterDefault();
        // Check for a defined filter to use.
        if (isset($this->filters[$filter_name]['filter'])) {
          $filter = new $this->filters[$filter_name]['filter']();
        }
        $filter_results = $filter->filterQuery($this->query, $this->filters[$filter_name], $values, $this->route['requirements']['type']);

        // Set the query to the modified version if returned.
        if (isset($filter_results['query'])) {
          $this->query = $filter_results['query'];
        }

        // Set any postQueryFilters returned instead of a moded query.
        if (isset($filter_results['post_query_filters'])) {
          $this->postQueryFilters[] = $filter_results['post_query_filters'];
        }
      }
    }
  }

  /**
   * Sets any NOT operator filters on fields with multiple values passed along.
   */
  protected function setPostQueryFilters() {
    foreach ($this->postQueryFilters as $postQueryFilters) {
      // Set the default filter. Can be overriden later.
      $filter = new FilterDefault();
      // Check for a defined filter to use.
      if (isset($postQueryFilters['filter'])) {
        $filter = new $postQueryFilters['filter']();
      }
      $this->etids = $filter->filterPostQuery($this->etids, $postQueryFilters);
    }
  }

  /**
   * Sets the sorters for the query.
   */
  protected function setSorters() {
    // Set the sorter.
    $router_sorter = isset($this->sorters[$this->query_parameters['orderby']]) ? $this->sorters[$this->query_parameters['orderby']] : $this->sorters[$this->route['defaults']['orderby']];
    if (isset($router_sorter)) {
      // If a property sorter is given, set the orderby.
      if (isset($router_sorter['property']) && isset($router_sorter['sort'])) {
        $orderby = $router_sorter['property'];
      }
      // If a table is given, set the orderby and define the table/value to use.
      if (isset($router_sorter['table']) && isset($router_sorter['column']) && isset($router_sorter['sort'])) {
        // Join the needed table and set the orderby.
        $this->query->join($router_sorter['table'], $router_sorter['table'], $router_sorter['table'] . '.entity_id = ' . $this->route['requirements']['type'] . '.' . $this->entity_identifier);
        $orderby = $router_sorter['table'] . '.' . $router_sorter['column'];
      }
      // Set the sort.
      $sort = isset($this->query_parameters['sort']) ? $this->query_parameters['sort'] : $router_sorter['sort'];
    }

    // Set the limit.
    $limit = isset($this->query_parameters['limit']) ? $this->query_parameters['limit'] : $this->route['defaults']['limit'];

    // Now add the orderBy commands.
    if (isset($orderby) && isset($sort)) {
      $this->query->orderBy($orderby, $sort);
    }

    $start = isset($this->query_parameters['start']) ? $this->query_parameters['start'] : 0;
    // Set the sorting if a limit has been provided.
    if (isset($limit)) {
      $this->query->range($start, $limit);
    }
  }

  /**
   * Formats a node into an array to return to the client app.
   * @return array
   *   A node formatted into an array.
   */
  protected function formatEntities() {
    // Load the entities.
    $unformatted_entities = entity_load($this->route['requirements']['type'], $this->etids);
    $formatted_entities = array();

    if (!empty($unformatted_entities)) {
      // Use the provided mapping.
      if (isset($this->mappings)) {
        foreach ($unformatted_entities as $etid => $entity) {
          foreach ($this->mappings as $field_name => $map) {
            $formatter = new $map['formatter']();
            // Call the appropriete formatter.
            $formatted_entities[$etid][$map['label']] = $formatter->format($entity, $this->route['requirements']['type'], $field_name);
          }
        }
        return array_values($formatted_entities);
      }

      // Otherwise just return the unformatted entities.
      return array_values($unformatted_entities);
    }

    // Return an error response if no results were returned.
    http_response_code(204);
    return array(
      'status' => 'no_results',
      'message' => t('There are no entities that match the given conditions.'),
    );
  }
}
