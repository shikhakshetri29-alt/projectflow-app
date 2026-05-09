<?php
/**
 * PHP built-in server router.
 * Serves static files directly; routes everything else to the correct PHP file.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve existing static files (CSS, JS, images, fonts)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false; // Let the built-in server handle it
}

// Route to index.php if no specific file
if ($uri === '/') {
    require __DIR__ . '/index.php';
    return;
}

// Map URI to file path
$file = __DIR__ . $uri;

// Try exact match
if (file_exists($file) && !is_dir($file)) {
    require $file;
    return;
}

// Try adding .php
if (file_exists($file . '.php')) {
    require $file . '.php';
    return;
}

// Try directory index
if (is_dir($file) && file_exists($file . '/index.php')) {
    require $file . '/index.php';
    return;
}

// 404
http_response_code(404);
echo '<h1>404 Not Found</h1><p>The page <code>' . htmlspecialchars($uri) . '</code> was not found.</p>';
