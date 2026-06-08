<?php
session_start();
require_once __DIR__ . '/../db/connection.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getPDO();

    switch ($action) {
        case 'add':
            handleAddReview($pdo);
            break;

        case 'getBycar':
            handleGetCarReviews($pdo);
            break;

        case 'getBySeller':
            handleGetSellerReviews($pdo);
            break;

        case 'getAll':
            handleGetAllReviews($pdo);
            break;

        default:
            http_response_code(400);
            exit(json_encode(['error' => 'Invalid action']));
    }
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['error' => $e->getMessage()]));
}

function handleAddReview($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        exit(json_encode(['error' => 'Unauthorized']));
    }

    $reviewerId = $_SESSION['user_id'];
    $carId = (int)($_POST['car_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = cleanInput($_POST['comment'] ?? '');

    if (!$carId || $rating < 1 || $rating > 5) {
        http_response_code(400);
        exit(json_encode(['error' => 'Invalid car ID or rating']));
    }

    // Verify car exists
    $carQuery = 'SELECT id, seller_id FROM cars WHERE id = :id LIMIT 1';
    $carStmt = $pdo->prepare($carQuery);
    $carStmt->execute([':id' => $carId]);
    $car = $carStmt->fetch();

    if (!$car) {
        http_response_code(404);
        exit(json_encode(['error' => 'Car not found']));
    }

    // Check if user already reviewed this car
    $existingQuery = 'SELECT id FROM reviews WHERE car_id = :car_id AND reviewer_id = :reviewer_id LIMIT 1';
    $existingStmt = $pdo->prepare($existingQuery);
    $existingStmt->execute([
        ':car_id' => $carId,
        ':reviewer_id' => $reviewerId
    ]);

    if ($existingStmt->fetch()) {
        http_response_code(400);
        exit(json_encode(['error' => 'You have already reviewed this car']));
    }

    $insertQuery = 'INSERT INTO reviews (car_id, reviewer_id, rating, comment)
                    VALUES (:car_id, :reviewer_id, :rating, :comment)';

    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([
        ':car_id' => $carId,
        ':reviewer_id' => $reviewerId,
        ':rating' => $rating,
        ':comment' => $comment
    ]);

    // Update seller rating
    $avgQuery = 'SELECT AVG(rating) as avg_rating, COUNT(*) as total
                 FROM reviews
                 WHERE car_id IN (SELECT id FROM cars WHERE seller_id = :seller_id)';
    $avgStmt = $pdo->prepare($avgQuery);
    $avgStmt->execute([':seller_id' => $car['seller_id']]);
    $result = $avgStmt->fetch();

    $updateQuery = 'UPDATE users SET rating = :rating, total_ratings = :total WHERE id = :id';
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([
        ':rating' => round($result['avg_rating'], 1),
        ':total' => $result['total'],
        ':id' => $car['seller_id']
    ]);

    exit(json_encode([
        'success' => true,
        'message' => 'Review added successfully',
        'review_id' => $pdo->lastInsertId()
    ]));
}

function handleGetCarReviews($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $carId = (int)($_GET['car_id'] ?? 0);

    if (!$carId) {
        http_response_code(400);
        exit(json_encode(['error' => 'Car ID required']));
    }

    $query = 'SELECT r.*, u.username, u.first_name, u.last_name
              FROM reviews r
              JOIN users u ON r.reviewer_id = u.id
              WHERE r.car_id = :car_id
              ORDER BY r.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute([':car_id' => $carId]);
    $reviews = $stmt->fetchAll();

    exit(json_encode([
        'success' => true,
        'reviews' => $reviews
    ]));
}

function handleGetSellerReviews($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $sellerId = (int)($_GET['seller_id'] ?? 0);

    if (!$sellerId) {
        http_response_code(400);
        exit(json_encode(['error' => 'Seller ID required']));
    }

    $query = 'SELECT r.*, u.username, u.first_name, u.last_name, c.brand, c.model, c.year
              FROM reviews r
              JOIN users u ON r.reviewer_id = u.id
              JOIN cars c ON r.car_id = c.id
              WHERE c.seller_id = :seller_id
              ORDER BY r.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute([':seller_id' => $sellerId]);
    $reviews = $stmt->fetchAll();

    exit(json_encode([
        'success' => true,
        'reviews' => $reviews
    ]));
}

function handleGetAllReviews($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $query = 'SELECT r.*, u.username, u.first_name, u.last_name, c.brand, c.model, c.year
              FROM reviews r
              JOIN users u ON r.reviewer_id = u.id
              JOIN cars c ON r.car_id = c.id
              ORDER BY r.created_at DESC
              LIMIT 50';

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $reviews = $stmt->fetchAll();

    exit(json_encode([
        'success' => true,
        'reviews' => $reviews
    ]));
}
