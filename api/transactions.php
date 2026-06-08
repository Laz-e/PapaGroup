<?php
session_start();
require_once __DIR__ . '/../db/connection.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

try {
    $pdo = getPDO();

    switch ($action) {
        case 'create':
            handleCreateTransaction($pdo);
            break;

        case 'getBuyer':
            handleGetBuyerTransactions($pdo);
            break;

        case 'getSeller':
            handleGetSellerTransactions($pdo);
            break;

        case 'getAll':
            handleGetAllTransactions($pdo);
            break;

        case 'update':
            handleUpdateTransaction($pdo);
            break;

        case 'getById':
            handleGetTransactionById($pdo);
            break;

        case 'report':
            handleSalesReport($pdo);
            break;

        default:
            http_response_code(400);
            exit(json_encode(['error' => 'Invalid action']));
    }
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['error' => $e->getMessage()]));
}

function handleCreateTransaction($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $buyerId = $_SESSION['user_id'];
    $carId = (int)($_POST['car_id'] ?? 0);
    $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
    $notes = cleanInput($_POST['notes'] ?? '');

    if (!$carId || !$purchasePrice) {
        http_response_code(400);
        exit(json_encode(['error' => 'Missing required fields']));
    }

    // Get car and verify it exists and is available
    $carQuery = 'SELECT id, seller_id, price, status FROM cars WHERE id = :id LIMIT 1';
    $carStmt = $pdo->prepare($carQuery);
    $carStmt->execute([':id' => $carId]);
    $car = $carStmt->fetch();

    if (!$car) {
        http_response_code(404);
        exit(json_encode(['error' => 'Car not found']));
    }

    if ($car['status'] === 'sold') {
        http_response_code(400);
        exit(json_encode(['error' => 'Car is already sold']));
    }

    if ($buyerId == $car['seller_id']) {
        http_response_code(400);
        exit(json_encode(['error' => 'You cannot buy your own car']));
    }

    // Create transaction
    $insertQuery = 'INSERT INTO transactions (car_id, buyer_id, seller_id, purchase_price, transaction_status, notes)
                    VALUES (:car_id, :buyer_id, :seller_id, :purchase_price, :transaction_status, :notes)';

    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([
        ':car_id' => $carId,
        ':buyer_id' => $buyerId,
        ':seller_id' => $car['seller_id'],
        ':purchase_price' => $purchasePrice,
        ':transaction_status' => 'pending',
        ':notes' => $notes
    ]);

    $transactionId = $pdo->lastInsertId();

    $updateCarQuery = 'UPDATE cars SET status = "sold" WHERE id = :car_id';
    $updateCarStmt = $pdo->prepare($updateCarQuery);
    $updateCarStmt->execute([':car_id' => $carId]);

    exit(json_encode([
        'success' => true,
        'message' => 'Transaction created successfully',
        'transaction_id' => $transactionId
    ]));
}

function handleGetBuyerTransactions($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $buyerId = $_SESSION['user_id'];
    $status = cleanInput($_GET['status'] ?? '');

    $query = 'SELECT t.*, c.brand, c.model, c.year, c.price, c.image_path,
                     u.username, u.first_name, u.last_name, u.phone
              FROM transactions t
              JOIN cars c ON t.car_id = c.id
              JOIN users u ON t.seller_id = u.id
              WHERE t.buyer_id = :buyer_id';

    $params = [':buyer_id' => $buyerId];

    if ($status) {
        $query .= ' AND t.transaction_status = :status';
        $params[':status'] = $status;
    }

    $query .= ' ORDER BY t.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    exit(json_encode([
        'success' => true,
        'transactions' => $transactions
    ]));
}

function handleGetSellerTransactions($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $sellerId = $_SESSION['user_id'];
    $status = cleanInput($_GET['status'] ?? '');

    $query = 'SELECT t.*, c.brand, c.model, c.year, c.price, c.image_path,
                     u.username, u.first_name, u.last_name, u.phone, u.email
              FROM transactions t
              JOIN cars c ON t.car_id = c.id
              JOIN users u ON t.buyer_id = u.id
              WHERE t.seller_id = :seller_id';

    $params = [':seller_id' => $sellerId];

    if ($status) {
        $query .= ' AND t.transaction_status = :status';
        $params[':status'] = $status;
    }

    $query .= ' ORDER BY t.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    exit(json_encode([
        'success' => true,
        'transactions' => $transactions
    ]));
}

function handleGetAllTransactions($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    // Only managers can view all transactions
    if ($_SESSION['role'] !== 'manager') {
        http_response_code(403);
        exit(json_encode(['error' => 'Forbidden']));
    }

    $query = 'SELECT t.*, c.brand, c.model, c.year, c.price,
                     buyer.username as buyer_username, buyer.first_name as buyer_first_name, buyer.last_name as buyer_last_name, buyer.phone as buyer_phone,
                     seller.username as seller_username, seller.first_name as seller_first_name, seller.last_name as seller_last_name
              FROM transactions t
              JOIN cars c ON t.car_id = c.id
              JOIN users buyer ON t.buyer_id = buyer.id
              JOIN users seller ON t.seller_id = seller.id
              ORDER BY t.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $transactions = $stmt->fetchAll();

    exit(json_encode([
        'success' => true,
        'transactions' => $transactions
    ]));
}

function handleUpdateTransaction($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $userId = $_SESSION['user_id'];
    $transactionId = (int)($_POST['transaction_id'] ?? 0);
    $status = cleanInput($_POST['status'] ?? '');

    if (!$transactionId || !in_array($status, ['pending', 'completed', 'cancelled'])) {
        http_response_code(400);
        exit(json_encode(['error' => 'Invalid transaction ID or status']));
    }

    // Get transaction to verify ownership
    $query = 'SELECT buyer_id, seller_id, car_id FROM transactions WHERE id = :id LIMIT 1';
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $transactionId]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        http_response_code(404);
        exit(json_encode(['error' => 'Transaction not found']));
    }

    // Only buyer, seller, or manager can update
    $canUpdate = ($userId == $transaction['buyer_id'] || $userId == $transaction['seller_id'] || $_SESSION['role'] === 'manager');

    if (!$canUpdate) {
        http_response_code(403);
        exit(json_encode(['error' => 'Forbidden']));
    }

    // Update transaction
    $updateQuery = 'UPDATE transactions SET transaction_status = :status WHERE id = :id';
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([
        ':status' => $status,
        ':id' => $transactionId
    ]);

    // If transaction is completed, mark car as sold
    if ($status === 'completed') {
        $carUpdateQuery = 'UPDATE cars SET status = "sold" WHERE id = :car_id';
        $carUpdateStmt = $pdo->prepare($carUpdateQuery);
        $carUpdateStmt->execute([':car_id' => $transaction['car_id']]);
    }

    exit(json_encode([
        'success' => true,
        'message' => 'Transaction updated successfully'
    ]));
}

function handleGetTransactionById($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        exit(json_encode(['error' => 'Method not allowed']));
    }

    $userId = $_SESSION['user_id'];
    $transactionId = (int)($_GET['id'] ?? 0);

    if (!$transactionId) {
        http_response_code(400);
        exit(json_encode(['error' => 'Transaction ID required']));
    }

    $query = 'SELECT t.*, c.brand, c.model, c.year, c.price, c.image_path,
                     buyer.username as buyer_username, buyer.first_name as buyer_first_name, buyer.last_name as buyer_last_name,
                     seller.username as seller_username, seller.first_name as seller_first_name, seller.last_name as seller_last_name
              FROM transactions t
              JOIN cars c ON t.car_id = c.id
              JOIN users buyer ON t.buyer_id = buyer.id
              JOIN users seller ON t.seller_id = seller.id
              WHERE t.id = :id LIMIT 1';

    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $transactionId]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        http_response_code(404);
        exit(json_encode(['error' => 'Transaction not found']));
    }

    // Verify user can view this transaction
    if ($userId != $transaction['buyer_id'] && $userId != $transaction['seller_id'] && $_SESSION['role'] !== 'manager') {
        http_response_code(403);
        exit(json_encode(['error' => 'Forbidden']));
    }

    exit(json_encode([
        'success' => true,
        'transaction' => $transaction
    ]));
}


function handleSalesReport($pdo){
    $stmt=$pdo->query("SELECT DATE_FORMAT(created_at,'%b') m, COUNT(*) sales, SUM(purchase_price) revenue FROM transactions GROUP BY MONTH(created_at) ORDER BY MONTH(created_at)");
    $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    $labels=[];$sales=[];
    foreach($rows as $r){$labels[]=$r['m'];$sales[]=(int)$r['sales'];}
    exit(json_encode(['success'=>true,'labels'=>$labels,'sales'=>$sales]));
}
