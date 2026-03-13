<?php

/**
 * @file
 * Bootstrap for PHPUnit tests.
 */

$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
  require $autoloader;
}
