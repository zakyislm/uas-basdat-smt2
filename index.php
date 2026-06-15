<?php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$path = rtrim($path, '/');

if (file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path) && $path !== '' && basename($path) !== 'index.php') {
    return false; 
}

if ($path === '' || $path === '/') {
    require 'home.php';
    exit;
}

$file = __DIR__ . $path . '.php';
if (file_exists($file)) {
    if (basename($file) === 'index.php') {
        require 'home.php';
        exit;
    }
    require $file;
    exit;
}

http_response_code(404);
require '404.php';
exit;
