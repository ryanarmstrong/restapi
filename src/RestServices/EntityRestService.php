<?php
/**
 * @file
 * rest.inc
 */

namespace Drupal\restapi\RestServices;

use Drupal\restapi\RestServiceInterface;

/**
 * Provides a base RESTful service class.
 */
class EntityRestService implements RestServiceInterface {
  /**
   * The caching options to use.
   *
   * @var array
   */
  protected $caching_settings;

  /**
   * The entity key identifier.
   *
   * @var array
   */
  protected $entity_identifier;

  /**
   * The entity info for the entity type being used in the service.
   *
   * @var array
   */
  protected $entity_info;

  /**
   * The entity IDs of the requested resources.
   *
   * @var array
   */
  protected $etids = array();

  /**
   * The filters supported by the service.
   *
   * @var array
   */
  protected $filters = array();

  /**
   * The headers of the reponse.
   *
   * @var array
   */
  protected $headers = array();

  /**
   * The mappers used by the service.
   *
   * @var array
   */
  protected $mappings;

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
   * The validation status of the request.
   *
   * @var array
   */
  protected $validation;

  /**
   * An array of options passed to the Service.
   *
   * @var array
   */
  public $variables;

  /**
   * RestService contructor. Returns validation response.
   *
   * @param string $route_id
   *   The ID of the route.
   * @param array $variables
   *   The variables available to the RestService.
   * @return array
   *   Returns the validation response for the client.
   */
  public function __construct($route_id, $variables) {
    $this->request = $_SERVER;
    $this->variables = $variables;

    // Setup and array for a resource collection or just set the entity id for a single resource.
    $this->etids = !empty($variables['etid']) ? array($variables['etid']) : array();

    // Load query string.
    $this->query_parameters = drupal_get_query_parameters();

    // Store the configuration for this route.
    $this->getCachingSettings();
    $this->route = restapi_service_config('routes', $route_id, $this->caching_settings['restapi_cache_configuration']);

    // Validate the response. If it doesn't validate we don't need to do anything.
    $this->validation = $this->validateRequest();
    if ($this->validation === TRUE) {
      // Initialize the $response variable.
      switch ($this->route['cardinality']) {
        case 'collection':
          $this->response = array();
          break;
        case 'singleton':
          $this->response = new \stdClass();
          break;
      }

      // Set the entity identifier.
      $this->entity_info = entity_get_info($this->route['requirements']['type']);
      $this->entity_identifier = $this->entity_info['entity keys']['id'];

      // Setup the query object.
      $this->query = db_select($this->entity_info['base table']);
      $this->query->fields($this->entity_info['base table'], array($this->entity_identifier));

      // Load the mapper, if defined.
      $this->mappings = restapi_service_config('mappers', $this->requestMapper(), $this->caching_settings['restapi_cache_configuration']);

      // Load defined filters and save the ones to use for this route.
      if (isset($this->route['defaults']['filter'])) {
        $this->filters = restapi_service_config('filters', $this->route['defaults']['filter'], $this->caching_settings['restapi_cache_configuration']);
      }

      // Store the sorters for this route.
      if (isset($this->route['defaults']['sorter'])) {
        $this->sorters = restapi_service_config('sorters', $this->route['defaults']['sorter'], $this->caching_settings['restapi_cache_configuration']);
      }
    }
  }

  /**
   * Generates a response to send to the client.
   * @return array
   *   Returns the response for the client.
   */
  public function generateResponse() {
    if (empty($this->etids) && $this->validation === TRUE) {
      $this->retrieveEntities($this->caching_settings['restapi_cache_collections']);
    } elseif($this->validation !== TRUE) {
      return $this->validation;
    }
    $this->formatResponse($this->caching_settings['restapi_cache_content']);
    $this->headers['ETag'] = hash('sha256', serialize($this->response));
    $this->setHeaders($this->caching_settings['restapi_cache_headers']);
    return $this->getResponse();
  }

  /**
   * Returns the entity IDs that were collected by the request.
   * @return array
   *   An array of entity IDs.
   */
  public function getEntityIds() {
    if (empty($this->etids) && $this->validation === TRUE) {
      $this->retrieveEntities();
    } else {
      $this->setHeaders();
      return $this->validation;
    }
    return $this->etids;
  }

  public function setHeaders($caching_enabled) {
    $path = $_SERVER['REQUEST_URI'];
    $cache = cache_get("$path", 'cache_restapi_headers');
    if (!$cache || !$caching_enabled) {
      //$this->setDrupalCacheHeader('MISS');
      if (!empty($this->headers) && $caching_enabled) {
        cache_set("$path", $this->headers, 'cache_restapi_headers');
      }
    } else {
      //$this->setDrupalCacheHeader('HIT');
      $this->headers = $cache->data;
    }
    foreach ($this->headers as $key => $value) {
      drupal_add_http_header($key,$value, TRUE);
    }
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
    if (!empty($this->request['HTTP_ACCEPT']) && (strpos($this->request['HTTP_ACCEPT'], '*/*') === FALSE && !in_array($this->request['HTTP_ACCEPT'], $this->route['content_types']))) {
      http_response_code(406);
      return array(
        'status' => 'unsupported_content_type',
        'message' => t('This service does not support the @format format.', array('@format' => $this->request['HTTP_ACCEPT'])),
        'format' => $this->request['HTTP_ACCEPT'],
      );
    }

    return TRUE;
  }

  /**
   * Retrieves the entities that pass through the given filters.
   * @return array
   *   Returns an array of formatted entities.
   */
  protected function retrieveEntities($caching_enabled) {
    $path = $_SERVER['REQUEST_URI'];
    $cache = cache_get("$path", 'cache_restapi_collections');
    if (!$cache || !$caching_enabled) {
      $this->setRequirements();
      // Call custom requirement callback if provided.
      if (isset($this->route['requirements']['custom_callback'])) {
        call_user_func($this->route['requirements']['custom_callback'], array($this->variables, $this->query));
      }
      $this->filterResponse();
      $this->sortResponse();

      $results = $this->query->execute();
      $entity_identifier = $this->entity_identifier;
      foreach ($results as $value) {
        $this->etids[$value->$entity_identifier] = $value->$entity_identifier;
      }

      if (!empty($this->etids) && $caching_enabled) {
        cache_set("$path", $this->etids, 'cache_restapi_collections');
        $this->headers['Cache-Control'] = 'public,max-age=86400,s-maxage=86400';
        $this->headers['Last-Modified'] = date('D, d M Y G:i:s e');
        $this->headers['Expires'] = date('D, d M Y G:i:s e', time() + 86400);
      }
    } else {
      $this->etids = $cache->data;
    }
  }

  /**
   * Sets entity requirements.
   */
  protected function setRequirements() {
    // Set any defined entity property requirements.
    foreach ($this->route['requirements']['properties'] as $property => $value) {
      $this->query->condition($this->entity_info['base table'] . '.' . $property, $value);
    }
  }

  /**
   * Sets the filters for the query.
   */
  protected function filterResponse() {
    // Loop through the filters.
    $filter_list = $this->buildFilterList();
    foreach ($filter_list as $filter_name => $filter_definition) {
      // Set the filter and run it.
      $filter_type = isset($filter_definition['filter']) ? $filter_definition['filter'] : '\Drupal\restapi\Filters\FilterBase';
      $filter = new $filter_type($filter_definition);
      $filter->filterQuery($this->query);
    }
  }

  /**
   * Sets the sorters for the query.
   */
  protected function sortResponse() {
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
        $this->query->join($router_sorter['table'], $router_sorter['table'], $router_sorter['table'] . '.entity_id = ' . $this->entity_info['base table'] . '.' . $this->entity_identifier);
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

    $this->headers['X-Total-Count'] = $this->query->countQuery()->execute()->fetchField();

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
  protected function formatResponse($caching_enabled) {
    foreach ($this->etids as $etid) {
      $mapper = $this->requestMapper();
      $cache = cache_get($this->route['requirements']['type'] . ':' . $this->route['requirements']['bundle'] . ":$etid:" . $this->variables['region'] . ":$mapper", 'cache_restapi_content');
      if (!$cache || !$caching_enabled) {
        $entity = reset(entity_load($this->route['requirements']['type'], array($etid)));
        // Use the provided mapping.
        if (isset($this->mappings)) {
          $formatted_entity = new \stdClass();
          foreach ($this->mappings as $field_name => $map) {
            // Check for a custom formatter, use FormatterBase otherwise. Then format the field.
            $formatter_type = isset($map['formatter']) ? $map['formatter'] : '\Drupal\restapi\Formatters\FormatterBase';
            $formatter = new $formatter_type($entity, $this->route['requirements']['type'], $field_name, $this->variables);
            $formatted_entity->$map['label'] = $formatter->format();
          }
          $this->setResponse($formatted_entity);
          if ($caching_enabled) {
            cache_set($this->route['requirements']['type'] . ':' . $this->route['requirements']['bundle'] . ":$etid:" . $this->variables['region'] . ":$mapper", $formatted_entity, 'cache_restapi_content');
          }
        } else {
          // Otherwise just return the unformatted entities.
          $this->setResponse($entity);
        }
      } else {
        $this->setResponse($cache->data);
      }
    }
  }

  /**
   * Determins the correct X-Drupal-Cache setting.
   * @return string
   *   The name of the mapper to use.
   */
  protected function setResponse($item) {
    switch ($this->route['cardinality']) {
      case 'collection':
        $this->response[] = $item;
        break;
      case 'singleton':
        $this->response = $item;
        break;
    }
  }

  /**
   * Determins the correct X-Drupal-Cache setting.
   * @return string
   *   The name of the mapper to use.
   */
  protected function getResponse() {
    return $this->response;
  }

  /**
   * Helper function that builds the query filters to be run.
   */
  protected function buildFilterList() {
    $filter_list = array();
    // First find any filters that have defaults, which should be run.
    foreach ($this->filters as $filter_name => $filter) {
      if (isset($filter['default'])) {
        $filter_list[$filter_name] = $filter;
        $filter_list[$filter_name]['parameter'] = $filter_name;
        $filter_list[$filter_name]['value'] = $filter['default'];
      }
    }
    // Now find any passed filters in the query string.
    foreach ($this->query_parameters as $filter_name => $value) {
      if (isset($this->filters[$filter_name])) {
        $filter_list[$filter_name] = $this->filters[$filter_name];
        $filter_list[$filter_name]['parameter'] = $filter_name;
        $filter_list[$filter_name]['value'] = $value;
      }
    }
    return $filter_list;
  }

  /**
   * Returns the name of the mapper to use.
   * @return string
   *   The name of the mapper to use.
   */
  protected function requestMapper() {
    // Override the caller mapper with a mapper provided by the client request.
    if (isset($this->query_parameters['mapper'])) {
      return $this->query_parameters['mapper'];
    }
    // Override the route default with a mapper provided by the caller.
    if (isset($this->variables['mapper'])) {
      return $this->variables['mapper'];
    }
    // Load the default mapper defined by the route.
    return !empty($this->route['defaults']['mapper']) ? $this->route['defaults']['mapper'] : '';
  }

  /**
   * Get the caching settings.
   * @return string
   *   The name of the mapper to use.
   */
  protected function getCachingSettings() {
    $this->caching_settings['restapi_cache_configuration'] = variable_get('restapi_cache_configuration', 0);
    $this->caching_settings['restapi_cache_collections'] = variable_get('restapi_cache_collections', 0);
    $this->caching_settings['restapi_cache_headers'] = variable_get('restapi_cache_headers', 0);
    $this->caching_settings['restapi_cache_content'] = variable_get('restapi_cache_content', 0);
  }
}
