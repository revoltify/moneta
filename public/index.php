<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$installed = false;
$envPath = __DIR__.'/../.env';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    foreach (explode("\n", $envContent) as $line) {
        if (str_starts_with(trim($line), 'APP_KEY=base64:')) {
            $installed = true;
            break;
        }
    }
}

if (file_exists($installer = __DIR__.'/install/index.php') && ! $installed) {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);

    if (str_ends_with($uri, '/install') || $uri === $scriptDir.'/install') {
        require $installer;
        exit;
    }

    header('Location: '.$scriptDir.'/install');
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
