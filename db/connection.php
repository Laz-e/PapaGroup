<?php
// Database connection settings

define('DB_HOST', 'localhost');
define('DB_NAME', 'papagroup');
define('DB_USER', 'root');
define('DB_PASS', '');

function getPDO()
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            exit('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function cleanInput($value)
{
    return trim(htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}
