<?php
// CRUD handlers for "books" table

function list_items() {
    $stmt = db()->query('SELECT id, title, author FROM books ORDER BY id DESC');
    echo json_encode($stmt->fetchAll());
}

function get_item($id) {
    $stmt = db()->prepare('SELECT id, title, author FROM books WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        return;
    }
    echo json_encode($row);
}

function create_item() {
    $input = json_decode(file_get_contents('php://input'), true);

    // TODO: add validation
    $title = $input['title'] ?? 'Untitled';
    $author = $input['author'] ?? null;

    $stmt = db()->prepare('INSERT INTO books(title, author) VALUES (?, ?)');
    $stmt->execute([trim($title), $author ? trim($author) : null]);

    http_response_code(201);
    echo json_encode(['id' => (int)db()->lastInsertId()]);
}

function update_item($id) {
    $input = json_decode(file_get_contents('php://input'), true);

    // TODO: add validation
    $title = $input['title'] ?? 'Untitled';
    $author = $input['author'] ?? null;

    $stmt = db()->prepare('UPDATE books SET title=?, author=? WHERE id=?');
    $stmt->execute([trim($title), $author ? trim($author) : null, $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        return;
    }

    echo json_encode(['ok' => true]);
}

function delete_item($id) {
    $stmt = db()->prepare('DELETE FROM books WHERE id=?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        return;
    }

    http_response_code(200);
    echo json_encode(['ok' => true]);
}
