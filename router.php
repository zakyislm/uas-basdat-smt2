<?php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if (file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    return false;
}

$path = rtrim($path, '/');

if ($path === '' || $path === '/') {
    require 'index.php';
    exit;
}

$file = __DIR__ . $path . '.php';
if (file_exists($file)) {
    require $file;
    exit;
}

http_response_code(404);
require '404.php';
exit;
