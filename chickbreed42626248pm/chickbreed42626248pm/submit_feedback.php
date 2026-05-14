<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'Chickacc');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO feedback (user_id, username, message) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $username, $message);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save feedback']);
}
$stmt->close();
$conn->close();
?>