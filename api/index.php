<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Vercel serverless entry point
|--------------------------------------------------------------------------
|
| A Vercel Function may only write durable runtime files to /tmp. Keep all
| generated Laravel manifests and compiled views there, and use cloud-backed
| services for application data, sessions, cache, and uploaded files.
|
*/

$setDefaultEnvironmentValue = static function (string $key, string $value): void {
    if (array_key_exists($key, $_ENV)
        || array_key_exists($key, $_SERVER)
        || getenv($key) !== false) {
        return;
    }

    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv($key.'='.$value);
};

$temporaryDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'laravel';
$compiledViewsDirectory = $temporaryDirectory.DIRECTORY_SEPARATOR.'views';

foreach ([$temporaryDirectory, $compiledViewsDirectory] as $directory) {
    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
        throw new RuntimeException('Tidak dapat menyiapkan direktori sementara Laravel.');
    }
}

$productionDefaults = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'LOG_CHANNEL' => 'stderr',
    'LOG_LEVEL' => 'warning',
    'SCHOOL_STORE' => 'firestore',
    'SESSION_DRIVER' => 'firestore',
    'CACHE_STORE' => 'firestore',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_SECURE_COOKIE' => 'true',
    'APP_CONFIG_CACHE' => $temporaryDirectory.DIRECTORY_SEPARATOR.'config.php',
    'APP_EVENTS_CACHE' => $temporaryDirectory.DIRECTORY_SEPARATOR.'events.php',
    'APP_PACKAGES_CACHE' => $temporaryDirectory.DIRECTORY_SEPARATOR.'packages.php',
    'APP_ROUTES_CACHE' => $temporaryDirectory.DIRECTORY_SEPARATOR.'routes.php',
    'APP_SERVICES_CACHE' => $temporaryDirectory.DIRECTORY_SEPARATOR.'services.php',
    'VIEW_COMPILED_PATH' => $compiledViewsDirectory,
];

foreach ($productionDefaults as $key => $value) {
    $setDefaultEnvironmentValue($key, $value);
}

if (getenv('APP_URL') === false) {
    $vercelHost = getenv('VERCEL_URL') ?: getenv('VERCEL_PROJECT_PRODUCTION_URL');

    if (is_string($vercelHost) && $vercelHost !== '') {
        $setDefaultEnvironmentValue('APP_URL', 'https://'.ltrim($vercelHost, '/'));
    }
}

require dirname(__DIR__).'/public/index.php';
