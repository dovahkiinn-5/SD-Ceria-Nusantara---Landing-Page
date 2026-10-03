<?php

// Preserve an existing application key and local data.
chdir(dirname(__DIR__));
if (!file_exists('.env') && !copy('.env.example', '.env')) {
    fwrite(STDERR, "Tidak dapat membuat .env.\n");
    exit(1);
}
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
if (!$app['config']->get('app.key')) {
    $result = $kernel->call('key:generate');
    echo $kernel->output();
    if ($result !== 0) exit($result);
}
echo "Konfigurasi lokal siap.\n";
