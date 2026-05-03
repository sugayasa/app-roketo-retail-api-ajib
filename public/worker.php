<?php

/*
 *---------------------------------------------------------------
 * FRANKENPHP WORKER SCRIPT
 *---------------------------------------------------------------
 * This script bootstraps the CodeIgniter application once and
 * keeps it in memory. Each request is handled by the same
 * application instance for maximum performance.
 */

use CodeIgniter\Boot;

// Path to the front controller directory
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// LOAD OUR PATHS CONFIG FILE
require FCPATH . '../app/Config/Paths.php';

$paths = new Config\Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

// Boot the application once
$app = Boot::bootWeb($paths);

// FrankenPHP worker loop
while ($request = \frankenphp_handle_request(function () use ($app) {
    // Handle each request with the pre-booted application
    $app->run();
})) {
    // Reset application state between requests if needed
    gc_collect_cycles();
}
