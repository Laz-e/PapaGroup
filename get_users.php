<?php
require_once __DIR__ . '/db/connection.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, username, first_name, last_name, email, phone, role, verified FROM users ORDER BY created_at DESC');
    $users = $stmt->fetchAll();
    echo json_encode($users);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load users.']);
}
