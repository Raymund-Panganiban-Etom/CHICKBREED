<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// CSRF validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message empty']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'Chickacc');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO feedback (user_id, username, message) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $username, $message);
$ok = $stmt->execute();
echo json_encode(['success' => $ok]);
$stmt->close();
$conn->close();
?>