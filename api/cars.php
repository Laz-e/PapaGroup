<?php
session_start();
require_once __DIR__ . '/../db/connection.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Verify user is logged in (except for GET all cars)
if ($method !== 'GET' || ($action !== 'getAll' && $action !== 'getById')) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        exit(json_encode(['error' => 'Unauthorized']));
    }
}

try {
    $pdo = getPDO();

    switch ($action) {
        case 'add':
            handleAddCar($pdo);
            break;

        case 'getAll':
            handleGetAllCars($pdo);
            break;

        case 'getBySeller':
            handleGetCarsBySeller($pdo);
            break;

        case 'getById':
            handleGetCarById($pdo);
            break;

        case 'updateStatus':
            handleUpdateCarStatus($pdo);
            break;

        case 'delete':
            handleDeleteCar($pdo);
            break;

        case 'getApproved':
            handleGetApprovedCars($pdo);
            break;

        default:
            http_response_code(400);
            exit(json_encode(['error' => 'Invalid action']));
    }
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['error' => $e->getMessage()]));
}

function handleAddCar($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $userId = $_SESSION['user_id'];
    $brand = cleanInput($_POST['brand'] ?? '');
    $model = cleanInput($_POST['model'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $transmission = cleanInput($_POST['transmission'] ?? '');
    $variant = cleanInput($_POST['variant'] ?? '');
    $mileage = (int)($_POST['mileage'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $city = cleanInput($_POST['city'] ?? '');

    if (!$brand || !$model || !$year || !$transmission || !$variant || !$mileage || !$price || !$city) {
        http_response_code(400);
        exit(json_encode(['error' => 'Missing required fields']));
    }

    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/cars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid('car_') . '_' . basename($_FILES['image']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $imagePath = 'uploads/cars/' . $fileName;
        }
    }

    $query = 'INSERT INTO cars (seller_id, brand, model, year, transmission, variant, mileage, price, image_path, city, status)
              VALUES (:seller_id, :brand, :model, :year, :transmission, :variant, :mileage, :price, :image_path, :city, :status)';

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':seller_id' => $userId,
        ':brand' => $brand,
        ':model' => $model,
        ':year' => $year,
        ':transmission' => $transmission,
        ':variant' => $variant,
        ':mileage' => $mileage,
        ':price' => $price,
        ':image_path' => $imagePath,
        ':city' => $city,
        ':status' => 'pending'
    ]);

    exit(json_encode([
        'success' => true,
        'message' => 'Car listing created successfully',
        'car_id' => $pdo->lastInsertId()
    ]));
}

function handleGetAllCars($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $query = 'SELECT c.*, u.username, u.first_name, u.last_name, u.phone 
              FROM cars c
              JOIN users u ON c.seller_id = u.id
              ORDER BY c.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $cars = $stmt->fetchAll();

    exit(json_encode([
        'success' => true,
        'cars' => $cars
    ]));
}

function handleGetCarsBySeller($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $userId = $_SESSION['user_id'];
    $status = cleanInput($_GET['status'] ?? '');

    $query = 'SELECT * FROM cars WHERE seller_id = :seller_id';
    $params = [':seller_id' => $userId];

    if ($status) {
        $query .= ' AND status = :status';
        $params[':status'] = $status;
    }

    $query .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $cars = $stmt->fetchAll();

    exit(json_encode([
        'success' => true,
        'cars' => $cars
    ]));
}

function handleGetCarById($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $carId = (int)($_GET['id'] ?? 0);

    if (!$carId) {
        http_response_code(400);
        exit(json_encode(['error' => 'Car ID required']));
    }

    $query = 'SELECT c.*, u.username, u.first_name, u.last_name, u.phone, u.email
              FROM cars c
              JOIN users u ON c.seller_id = u.id
              WHERE c.id = :id';

    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $carId]);
    $car = $stmt->fetch();

    if (!$car) {
        http_response_code(404);
        exit(json_encode(['error' => 'Car not found']));
    }

    exit(json_encode([
        'success' => true,
        'car' => $car
    ]));
}

function handleGetApprovedCars($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $query = 'SELECT c.id, c.brand, c.model, c.year, c.transmission, c.variant, c.mileage, c.price, c.image_path, c.city, c.created_at,
                     u.id as seller_id, u.username, u.first_name, u.last_name, u.phone, u.email
              FROM cars c
              JOIN users u ON c.seller_id = u.id
              WHERE c.status = "approved" AND c.status != "sold"
              ORDER BY c.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $cars = $stmt->fetchAll();

    exit(json_encode([
        'success' => true,
        'cars' => $cars
    ]));
}

function handleUpdateCarStatus($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    // Only managers can update car status
    if ($_SESSION['role'] !== 'manager') {
        http_response_code(403);
        exit(json_encode(['error' => 'Forbidden']));
    }

    $carId = (int)($_POST['car_id'] ?? 0);
    $status = cleanInput($_POST['status'] ?? '');

    if (!$carId || !in_array($status, ['pending', 'approved', 'rejected', 'sold'])) {
        http_response_code(400);
        exit(json_encode(['error' => 'Invalid car ID or status']));
    }

    $query = 'UPDATE cars SET status = :status WHERE id = :id';
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':status' => $status,
        ':id' => $carId
    ]);

    exit(json_encode([
        'success' => true,
        'message' => 'Car status updated'
    ]));
}

function handleDeleteCar($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $userId = $_SESSION['user_id'];
    $carId = (int)($_POST['car_id'] ?? 0);

    if (!$carId) {
        http_response_code(400);
        exit(json_encode(['error' => 'Car ID required']));
    }

    // Check if user owns the car
    $query = 'SELECT seller_id, image_path FROM cars WHERE id = :id LIMIT 1';
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $carId]);
    $car = $stmt->fetch();

    if (!$car) {
        http_response_code(404);
        exit(json_encode(['error' => 'Car not found']));
    }

    if ($car['seller_id'] != $userId && $_SESSION['role'] !== 'manager') {
        http_response_code(403);
        exit(json_encode(['error' => 'You can only delete your own listings']));
    }

    // Delete image if exists
    if ($car['image_path']) {
        $filePath = __DIR__ . '/../' . $car['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $deleteQuery = 'DELETE FROM cars WHERE id = :id';
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleteStmt->execute([':id' => $carId]);

    exit(json_encode([
        'success' => true,
        'message' => 'Car listing deleted'
    ]));
}
