<?php
/**
 * @file
 * rest.inc
 */

namespace Drupal\restapi;

use Symfony\Component\Yaml\Parser;

/**
 * Provides a YAML file discovery service.
 */
class YamlConfigDiscovery {
  public function parsedConfig($mask) {
    $routing_files = $this->discoverRoutes($mask . '.yml');
    $yaml = new Parser();
    foreach ($routing_files as $routing_file) {
      $parsed_config = $yaml->parse(file_get_contents($routing_file));
      // Flatten the array.
      foreach ($parsed_config as $key => $value) {
        $config[$key] = $value;
      }
    }

    return $config;
  }

  /**
   * Parse YAML routing files.
   * @return array
   *   An array of routes.
   */
  protected function discoverRoutes($mask) {
    $dependencies = $this->getDependentModules();
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
   * Get the active modules which are dependent on this module.
   *
   * @return array
   *   The module names which depend on the parent module.
   */
  protected function getDependentModules() {
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
