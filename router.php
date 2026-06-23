<?php
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);


if (file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    
    $basename = basename($path);
    if (substr($basename, 0, 1) === '_' && substr($basename, -4) === '.php') {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    return false;
}

$path = rtrim($path, '/');


if ($path === '' || $path === '/') {
    require 'index.php';
    exit;
}


$slug = ltrim($path, '/');


if (substr($slug, 0, 1) === '_') {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}


$file = __DIR__ . '/' . $slug . '.php';
if (file_exists($file)) {
    require $file;
    exit;
}


$slug_underscored = str_replace('-', '_', $slug);
if ($slug_underscored !== $slug) {
    $file = __DIR__ . '/' . $slug_underscored . '.php';
    if (file_exists($file)) {
        require $file;
        exit;
    }
}


http_response_code(404);
require '404.php';
exit;
