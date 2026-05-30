<?php
session_start();
require_once __DIR__ . '/db/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.html');
}

$username = strtolower(trim($_POST['username'] ?? ''));
$email = strtolower(trim($_POST['email'] ?? ''));
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$firstName = trim($_POST['fname'] ?? '');
$lastName = trim($_POST['lname'] ?? '');

if (!$username || !$email || !$phone || !$password || !$firstName || !$lastName) {
    exit('<p>All fields are required. <a href="register.html">Go back</a></p>');
}

if (!preg_match('/^[0-9]{11}$/', $phone)) {
    exit('<p>Phone number must be 11 digits. <a href="register.html">Go back</a></p>');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('<p>Invalid email address. <a href="register.html">Go back</a></p>');
}

$pdo = getPDO();

$query = 'SELECT id FROM users WHERE username = :username OR email = :email OR phone = :phone LIMIT 1';
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':username' => $username,
    ':email' => $email,
    ':phone' => $phone,
]);

if ($stmt->fetch()) {
    exit('<p>Username, email or phone already exists. <a href="register.html">Try again</a></p>');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$verified = filter_var($email, FILTER_VALIDATE_EMAIL) ? 1 : 0;

$insert = 'INSERT INTO users (username, email, phone, password_hash, first_name, last_name, verified)
           VALUES (:username, :email, :phone, :password_hash, :first_name, :last_name, :verified)';
$stmt = $pdo->prepare($insert);
$stmt->execute([
    ':username' => $username,
    ':email' => $email,
    ':phone' => $phone,
    ':password_hash' => $passwordHash,
    ':first_name' => $firstName,
    ':last_name' => $lastName,
    ':verified' => $verified,
]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Complete</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container" style="margin: 100px auto; max-width: 420px; text-align: center;">
        <h2>Registration Successful</h2>
        <p>Your account has been created successfully.</p>
        <p><strong>Status:</strong> <?php echo $verified ? 'Verified' : 'Unverified'; ?></p>
        <p><a href="index.html">Go to Login</a></p>
    </div>
</body>
</html>
