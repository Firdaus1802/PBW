<?php
// api/index.php - Vercel Serverless Function Router for DompetKu (PBW m6)

// Set working directory to m6 so relative paths work
chdir(__DIR__ . '/../m6');

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Normalize path
$path = trim($path, '/');
if (strpos($path, 'm6/') === 0) {
    $path = substr($path, 3);
}

// Default to index.php
if ($path === '' || $path === 'index.php') {
    require __DIR__ . '/../m6/index.php';
    exit;
}

// Check direct PHP files inside m6
$file = __DIR__ . '/../m6/' . $path;
if (file_exists($file) && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    exit;
}

// Fallback to index.php
require __DIR__ . '/../m6/index.php';
