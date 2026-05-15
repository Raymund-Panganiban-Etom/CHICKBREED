<?php
session_start();
header('Content-Type: application/json');


if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'Chickacc');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// Ensure CSRF token exists in session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// List of read‑only actions that do NOT require CSRF token
$read_only_actions = ['getStats', 'getUsers', 'getFeedback'];

// Validate CSRF token for all non‑read‑only POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, $read_only_actions)) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }
}

if ($action === 'getStats') {
    $totalUsers = $conn->query("SELECT COUNT(*) as count FROM credentialss")->fetch_assoc()['count'];
    $totalListings = $conn->query("SELECT COUNT(*) as count FROM locations")->fetch_assoc()['count'];
    $totalBuyers = $conn->query("SELECT COUNT(*) as count FROM buyers")->fetch_assoc()['count'];
    $totalInquiries = $conn->query("SELECT COUNT(*) as count FROM buyer_inquiries")->fetch_assoc()['count'];
    echo json_encode(['success' => true, 'stats' => [
        'totalUsers' => $totalUsers,
        'totalListings' => $totalListings,
        'totalBuyers' => $totalBuyers,
        'totalInquiries' => $totalInquiries
    ]]);
    exit;
}

if ($action === 'getUsers') {
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $sql = "SELECT c.Ids as user_id, c.User as username, c.is_admin,
                   (SELECT COUNT(*) FROM locations WHERE user_id = c.Ids) as listing_count,
                   (SELECT fullname FROM buyers WHERE user_id = c.Ids LIMIT 1) as fullname,
                   (SELECT email FROM buyers WHERE user_id = c.Ids LIMIT 1) as email
            FROM credentialss c";
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " WHERE c.User LIKE '%$search%' 
                  OR (SELECT fullname FROM buyers WHERE user_id = c.Ids LIMIT 1) LIKE '%$search%'
                  OR (SELECT email FROM buyers WHERE user_id = c.Ids LIMIT 1) LIKE '%$search%'";
    }
    $sql .= " ORDER BY c.Ids ASC";
    $result = $conn->query($sql);
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

if ($action === 'deleteUser') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        exit;
    }
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'You cannot delete your own account']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();
    try {
        // 1. Delete messages linked to inquiries of this user (as buyer or seller)
        $conn->query("DELETE FROM buyer_seller_messages WHERE inquiry_id IN (SELECT inquiry_id FROM buyer_inquiries WHERE user_id = $user_id OR seller_id = $user_id)");
        
        // 2. Delete seller_notifications
        $conn->query("DELETE FROM seller_notifications WHERE seller_id = $user_id OR user_id = $user_id");
        
        // 3. Delete buyer_inquiries
        $conn->query("DELETE FROM buyer_inquiries WHERE user_id = $user_id OR seller_id = $user_id");
        
        // 4. Delete buyer_profiles
        $conn->query("DELETE FROM buyer_profiles WHERE user_id = $user_id");
        
        // 5. Delete buyers record
        $conn->query("DELETE FROM buyers WHERE user_id = $user_id");
        
        // 6. Delete locations (listings)
        $conn->query("DELETE FROM locations WHERE user_id = $user_id");
        
        // 7. Finally, delete the user from credentialss
        $conn->query("DELETE FROM credentialss WHERE Ids = $user_id");
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete user error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Deletion failed: ' . $e->getMessage()]);
    }
    exit;
}

// Change admin's own password
if ($action === 'changeAdminPassword') {
    $user_id = (int)$_SESSION['user_id'];
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }
    if ($new !== $confirm) {
        echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
        exit;
    }
    if (strlen($new) < 6) {
        echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters']);
        exit;
    }

    // Fetch current hashed password
    $stmt = $conn->prepare("SELECT Pass FROM credentialss WHERE Ids = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!password_verify($current, $row['Pass'])) {
            echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
            exit;
        }
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE credentialss SET Pass = ? WHERE Ids = ?");
        $update->bind_param("si", $new_hash, $user_id);
        if ($update->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update password']);
        }
        $update->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
    $stmt->close();
    exit;
}

// Get all feedback messages
if ($action === 'getFeedback') {
    $sql = "SELECT feedback_id, user_id, username, message, created_at, is_read 
            FROM feedback ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $feedback = [];
    while ($row = $result->fetch_assoc()) {
        $feedback[] = $row;
    }
    // Mark all as read (optional: mark only viewed feedback)
    $conn->query("UPDATE feedback SET is_read = 1 WHERE is_read = 0");
    echo json_encode(['success' => true, 'feedback' => $feedback]);
    exit;
}

// Delete a feedback entry
if ($action === 'deleteFeedback') {
    $feedback_id = (int)($_POST['feedback_id'] ?? 0);
    if ($feedback_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid feedback ID']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ?");
    $stmt->bind_param("i", $feedback_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delete failed']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
$conn->close();
?>