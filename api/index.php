<?php
chdir(__DIR__ . '/../m6');

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

$path = trim($path, '/');
if (strpos($path, 'm6/') === 0) {
    $path = substr($path, 3);
}

if ($path === '' || $path === 'index.php') {
    require __DIR__ . '/../m6/index.php';
    exit;
}

$base_dir = realpath(__DIR__ . '/../m6');
$file = realpath($base_dir . '/' . $path);

if ($file && strpos($file, $base_dir) === 0 && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    exit;
}

require __DIR__ . '/../m6/index.php';
