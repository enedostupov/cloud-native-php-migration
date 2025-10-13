<?php
// Bootstrap file for PHPUnit

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/books.php';

// Clean and recreate test table
$pdo = db();
$pdo->exec('DROP TABLE IF EXISTS books');
$pdo->exec('CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)');
