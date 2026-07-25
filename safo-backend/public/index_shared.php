<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 * Modified for shared hosting (InfinityFree)
 */

// Check if running from public/ or root
$__dir = __DIR__;
$__bootstrap = file_exists($__dir.'/../bootstrap/app.php') ? $__dir.'/../bootstrap/app.php' : $__dir.'/bootstrap/app.php';

require $__dir.'/vendor/autoload.php';

$app = require_once $__bootstrap;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
