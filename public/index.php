<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

ini_set('log_errors', 1);
ini_set('error_log', 'php://stderr');

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// --- INI BAGIAN TERPENTING ---
// Kita paksa Laravel menggunakan folder /tmp untuk penyimpanan
// karena folder asli di Vercel bersifat Read-Only.
$storagePath = '/tmp/storage';

if (!is_dir($storagePath)) {
    mkdir($storagePath, 0777, true);
}

$app->useStoragePath($storagePath);
// -----------------------------

// Menangani request (Sesuai versi Laravel kamu)
// Jika Laravel 11 (karena kamu pakai Tailwind v4, asumsi saya ini Laravel terbaru):
$app->handleRequest(Request::capture());
