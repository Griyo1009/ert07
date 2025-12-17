<?php

// PENTING: Set environment variables DULU sebelum load Laravel
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'production';
$_SERVER['LOG_CHANNEL'] = $_ENV['LOG_CHANNEL'] = 'stderr';
$_SERVER['SESSION_DRIVER'] = $_ENV['SESSION_DRIVER'] = 'cookie';
$_SERVER['CACHE_DRIVER'] = $_ENV['CACHE_DRIVER'] = 'array';
$_SERVER['QUEUE_CONNECTION'] = $_ENV['QUEUE_CONNECTION'] = 'sync';

putenv('LOG_CHANNEL=stderr');
putenv('SESSION_DRIVER=cookie');
putenv('CACHE_DRIVER=array');

// Buat temp directories kalau takde
$dirs = [
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views', 
    '/tmp/storage/framework/cache/data'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// NOW load Laravel
require __DIR__ . '/../public/index.php';

// Jika masih error atau menggunakan Laravel 10 ke bawah, gunakan:
// $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
// $response = $kernel->handle(
//     $request = Illuminate\Http\Request::capture()
// );
// $response->send();
// $kernel->terminate($request, $response);