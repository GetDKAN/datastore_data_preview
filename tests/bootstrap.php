<?php

/**
 * @file
 * Bootstrap for PHPUnit tests.
 */

$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
  $loader = require $autoloader;

  // Register Drupal core module namespaces for mocking.
  $drupalCorePath = __DIR__ . '/../vendor/drupal/core';
  if (is_dir($drupalCorePath)) {
    $modulePaths = glob($drupalCorePath . '/modules/*/src');
    foreach ($modulePaths as $moduleSrcPath) {
      $moduleName = basename(dirname($moduleSrcPath));
      $loader->addPsr4("Drupal\\{$moduleName}\\", $moduleSrcPath);
    }
  }
}
