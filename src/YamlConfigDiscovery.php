<?php
/**
 * @file
 * rest.inc
 */

namespace Drupal\restapi;

use Symfony\Component\Yaml\Parser;

/**
 * Provides a base RESTful service class.
 */
class YamlConfigDiscovery {
  public function parsedConfig($mask) {
    $routing_files = $this->discover_routes($mask);
    $yaml = new Parser();
    foreach ($routing_files as $module_name => $routing_file) {
      $parsed_config = array_merge($yaml->parse(file_get_contents($routing_file)));
    }

    return $parsed_config;
  }

  /**
   * Parse YAML routing files.
   * @return array
   *   An array of routes.
   */
  protected function discover_routes($mask) {
    $dependencies = $this->get_dependent_modules();
    $routing_files = array();
    foreach ($dependencies as $dependency) {
      $module_path = drupal_get_path('module', $dependency);
      if (file_exists($module_path . '/' . $mask)) {
        $routing_files[$dependency] = $module_path . '/' . $mask;
      }
    }
    return $routing_files;
  }

  /**
   * Get the active modules which are dependent on this module
   *
   * @return array an array of module names which depend on the parent module
   */
  protected function get_dependent_modules() {
    $module_data = system_rebuild_module_data();
    $dependencies = array();
    foreach ($module_data as $module_name => $module) {
      if (in_array('restapi', $module->info['dependencies']) && $module->type == 'module') {
        $dependencies[] = $module_name;
      }
    }

    return $dependencies;
  }
}
