<?php
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Global error handler
set_exception_handler(function ($e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'internal_server_error',
        'message' => getenv('APP_ENV') === 'dev' ? $e->getMessage() : 'An error occurred'
    ]);
});

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/books.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Health check
if (preg_match('#^/health/?$#', $path)) {
    try {
        db()->query('SELECT 1');
        echo json_encode(['status' => 'ok', 'db' => 'connected']);
    } catch (Exception $e) {
        http_response_code(503);
        echo json_encode(['status' => 'error', 'db' => 'disconnected']);
    }
    exit;
}

// Route: /api/items
if (preg_match('#^/api/items/?$#', $path)) {
    if ($method === 'GET') {
        list_items();
    } elseif ($method === 'POST') {
        create_item();
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'method_not_allowed']);
    }
    exit;
}

// Route: /api/items/{id}
if (preg_match('#^/api/items/(\\d+)/?$#', $path, $m)) {
    $id = intval($m[1]);
    if ($method === 'GET') {
        get_item($id);
    } elseif ($method === 'PUT') {
        update_item($id);
    } elseif ($method === 'DELETE') {
        delete_item($id);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'method_not_allowed']);
    }
    exit;
}

// Default: not found
http_response_code(404);
echo json_encode(['error' => 'not_found']);
