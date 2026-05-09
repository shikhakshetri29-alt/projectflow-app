<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// /php-pmapp/ prefix strip karo (XAMPP legacy path fix)
$uri = preg_replace('#^/php-pmapp#', '', $uri);
if ($uri === '') $uri = '/';

// Serve static files (CSS, JS, images)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Root redirect
if ($uri === '/') {
    require __DIR__ . '/index.php';
    return;
}

$file = __DIR__ . $uri;

// Exact match
if (file_exists($file) && !is_dir($file)) {
    require $file;
    return;
}

// Try .php extension
if (file_exists($file . '.php')) {
    require $file . '.php';
    return;
}

// Directory index
if (is_dir($file) && file_exists($file . '/index.php')) {
    require $file . '/index.php';
    return;
}

// 404
http_response_code(404);
echo '<h1>404 Not Found</h1><p>The page <code>' . htmlspecialchars($uri) . '</code> was not found.</p>';
