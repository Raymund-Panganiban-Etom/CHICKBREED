<?php
// send_message.php - buyer sends message to seller about a listing
session_start();
require 'db.php';
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'forbidden']); exit; }
$listing_id = intval($_POST['listing_id'] ?? 0);
$body = trim($_POST['body'] ?? '');
if ($listing_id <= 0 || $body === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'invalid']); exit; }

// get seller_id
$stmt = $pdo->prepare("SELECT seller_id FROM listings WHERE id = :id LIMIT 1");
$stmt->execute([':id'=>$listing_id]);
$listing = $stmt->fetch();
if (!$listing) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'notfound']); exit; }

$insert = $pdo->prepare("INSERT INTO messages (listing_id, buyer_id, seller_id, sender, body) VALUES (:lid,:bid,:sid,'buyer',:body)");
$insert->execute([':lid'=>$listing_id,':bid'=>$_SESSION['user_id'],':sid'=>$listing['seller_id'],':body'=>$body]);
echo json_encode(['ok'=>true]);
?>