<?php
// seller_post.php
session_start();
require 'db.php';
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') { http_response_code(403); exit('Forbidden'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $desc  = trim($_POST['description']);
  $lat   = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
  $lon   = isset($_POST['lon']) ? floatval($_POST['lon']) : null;
  $stmt = $pdo->prepare("INSERT INTO listings (seller_id, title, description, lat, lon) VALUES (:sid,:t,:d,:lat,:lon)");
  $stmt->execute([':sid'=>$_SESSION['user_id'],':t'=>$title,':d'=>$desc,':lat'=>$lat,':lon'=>$lon]);
  header('Location: seller_dashboard.php'); exit;
}
?>
<!-- HTML form should collect title, description and hidden lat/lon filled by JS after consent -->
