<?php
session_start();
require_once __DIR__.'/../db/connection.php';
header('Content-Type: application/json');
if(!isset($_SESSION['user_id'])){http_response_code(401);exit;}
$pdo=getPDO(); $id=$_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD']==='PUT'){
$data=json_decode(file_get_contents('php://input'),true);
$stmt=$pdo->prepare("UPDATE users SET username=?,email=?,first_name=?,last_name=?,phone=? WHERE id=?");
$ok=$stmt->execute([$data['username']??'',$data['email']??'',$data['first_name']??'',$data['last_name']??'',$data['phone']??'',$id]);
echo json_encode(['success'=>$ok]); exit;
}

$stmt=$pdo->prepare("SELECT id,username,email,phone,first_name,last_name FROM users WHERE id=?");
$stmt->execute([$id]);
$user=$stmt->fetch(PDO::FETCH_ASSOC);
$b=$pdo->prepare("SELECT t.*,c.brand,c.model,c.year FROM transactions t JOIN cars c ON c.id=t.car_id WHERE t.buyer_id=?");
$b->execute([$id]);
$s=$pdo->prepare("SELECT t.*,c.brand,c.model,c.year FROM transactions t JOIN cars c ON c.id=t.car_id WHERE t.seller_id=?");
$s->execute([$id]);
echo json_encode(['user'=>$user,'bought'=>$b->fetchAll(PDO::FETCH_ASSOC),'sold'=>$s->fetchAll(PDO::FETCH_ASSOC)]);
