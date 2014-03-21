<?php
/**
 * @file
 * API documentation for the REST API module.
 */

/**
 * Documentation on how to define service filters.
 *
 * @return array
 *   An array of filter defintions.
 */
function hook_restapi_filters() {
  // Entity Property Example.
  $filters['language'] = array(
    'property' => 'language',
  );

  // Entity Field Example.
  $filters['genres'] = array(
    'field' => 'field_genres',
  );

  return $filters;
}

/**
 * Documentation on how to define service filters.
 *
 * @return array
 *   An array of filter defintions.
 */
function hook_restapi_mappings() {
  // Mapping example. Provide a mapping for a node type called app.
  $mappings['node']['app'] = array(
    'title' => array(
      'label' => 'title',
      'formatter' => '\Drupal\restapi\Formatters\FormatterProperty',
    ),
    'created' => array(
      'label' => 'created',
      'formatter' => '\Drupal\restapi\Formatters\FormatterProperty',
    ),
    'field_controller_mapper_profile' => array(
      'label' => 'controller_mapper_profile',
      'formatter' => '\Drupal\restapi\Formatters\FormatterField',
    ),
    'field_genres' => array(
      'label' => 'genres',
      'formatter' => '\Drupal\restapi\Formatters\FormatterTaxonomy',
    ),
  );

  return $mappings;
}
