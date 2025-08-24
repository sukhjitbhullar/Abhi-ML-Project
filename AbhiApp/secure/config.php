<?php
// config.php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// 1. Determine active environment
$serverEnv = getenv('APP_ENV') ?: null;
if ($serverEnv) {
    $activeEnv = $serverEnv;
} else {
    $host      = $_SERVER['HTTP_HOST'] ?? '';
    $activeEnv = (strpos($host, 'localhost') !== false)
        ? 'local'
        : 'production';
}

// 2. Locate and validate .env file
$envFile = ".env.$activeEnv";
$envPath = __DIR__ . '/' . $envFile;

if (! file_exists($envPath)) {
    throw new \RuntimeException(
        "Missing environment file: {$envFile} in " . __DIR__
    );
}

// 3. Load the selected environment file
Dotenv::createImmutable(__DIR__, $envFile)
    ->safeLoad();

// 4. Define constants
define('BASE_URL',    $_ENV['BASE_URL']    ?? '');
define('ENVIRONMENT', $_ENV['ENV']         ?? $activeEnv);
define('BASE_PATH', $_ENV['BASE_PATH'] ?? __DIR__);
