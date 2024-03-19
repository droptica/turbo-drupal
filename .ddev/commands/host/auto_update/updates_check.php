<?php

// Default Drupal classes.
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\update\UpdateManagerInterface;
use Symfony\Component\HttpFoundation\Request;

// Try to..
try {
  // Prepare command variables.
  $site = $argv[1] ?? 'default';
  $app_path = sprintf('./%s', $argv[2] ?? '/web');

  echo $site . '\n\n';
  echo $app_path . '\n\n';

  // Get to current path.
  chdir($app_path);

  // Require composer autoloader.
  $autoloader = require_once './../vendor/autoload.php';

  // Initialize drupal instance.
  $request = Request::createFromGlobals();
  $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
  $kernel->setSitePath("sites/$site");
  Settings::initialize($kernel->getAppRoot(), $kernel->getSitePath(), $autoloader);
  $kernel->boot();
  $kernel->preHandle($request);

  // Update data.
  update_refresh();
  update_fetch_data();

  // Get available updates.
  $available_updates = update_get_available(TRUE);
  $project_data = update_calculate_project_data($available_updates);

  // Prepare array of modules.
  $not_secure_projects = [];

  // Loop through each module.
  foreach ($project_data as $project) {
    // Check if that module status is equal to not secure.
    if ($project['status'] === UpdateManagerInterface::NOT_SECURE) {
      // If so then add it to array.
      $not_secure_projects[] = $project['name'] === 'drupal' ? 'drupal/core' : "drupal/{$project['name']}";
    }
  }

  // At the end print that array.
  print "<<" . implode(' ', $not_secure_projects) . ">>";
}
catch (Exception | Throwable $e) {
  // Print error in any other case.
  print $e->getMessage();
}
