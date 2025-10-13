<?php
function db() {
    // Database connection helper
    static $pdo = null;

    if ($pdo) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $db   = getenv('DB_NAME') ?: 'appdb';
    $user = getenv('DB_USER') ?: 'app';
    $pass = getenv('DB_PASS') ?: 'app';
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS books (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                author VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'db_connection_failed', 'message' => $e->getMessage()]);
        exit;
    }

    return $pdo;
}
