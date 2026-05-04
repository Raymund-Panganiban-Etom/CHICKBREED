<?php
// seller_messages.php - seller views messages for their listings
session_start();
require 'db.php';
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') { header('Location: login.php'); exit; }
$seller_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
  SELECT m.id, m.listing_id, m.body, m.created_at, m.is_read, u.username AS buyer_username, l.title
  FROM messages m
  JOIN users u ON u.id = m.buyer_id
  JOIN listings l ON l.id = m.listing_id
  WHERE m.seller_id = :sid
  ORDER BY m.created_at DESC
");
$stmt->execute([':sid'=>$seller_id]);
$messages = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Seller Messages</title></head>
<body>
  <h2>Messages for your listings</h2>
  <p><a href="seller.php">Post listing</a> | <a href="logout.php">Logout</a></p>
  <?php if (empty($messages)): ?>
    <p>No messages yet.</p>
  <?php else: foreach($messages as $m): ?>
    <div style="border:1px solid #eee;padding:8px;margin-bottom:8px">
      <div><strong>Listing:</strong> <?=htmlspecialchars($m['title'])?></div>
      <div><strong>From:</strong> <?=htmlspecialchars($m['buyer_username'])?> <em><?=htmlspecialchars($m['created_at'])?></em></div>
      <div style="margin-top:6px"><?=nl2br(htmlspecialchars($m['body']))?></div>
    </div>
  <?php endforeach; endif; ?>
</body>
</html>
