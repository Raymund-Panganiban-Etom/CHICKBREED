<?php
// buy_handler.php – uses existing login session (no manual user_id)
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/buy_errors.log');
ob_clean();

function send_json($data) {
    global $conn;
    if (isset($conn)) $conn->close();
    echo json_encode($data);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'Chickacc');
if ($conn->connect_error) {
    send_json(['success' => false, 'error' => 'Database connection failed']);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ---------- getSession – returns logged-in user from credentialss ----------
if ($action === 'getSession') {
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        send_json(['success' => true, 'user_id' => $_SESSION['user_id']]);
    } else {
        send_json(['success' => false, 'error' => 'Not logged in']);
    }
}

// ---------- saveBuyer – uses session user_id (must exist in credentialss) ----------
if ($action === 'saveBuyer') {
    // Must be logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
        send_json(['success' => false, 'error' => 'You must log in first']);
    }
    $user_id = (int)$_SESSION['user_id'];  // from login session, NOT from POST

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location_address = trim($_POST['location_address'] ?? '');
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $preferences = trim($_POST['preferences'] ?? '');
    $consent_text = trim($_POST['consent_text'] ?? '');
    $buyer_agent = trim($_POST['buyer_agent'] ?? '');

    if (empty($fullname) || empty($email) || empty($phone)) {
        send_json(['success' => false, 'error' => 'Missing required fields']);
    }

    // Ensure buyers table exists with proper FK to credentialss
    $conn->query("CREATE TABLE IF NOT EXISTS `buyers` (
        `buyer_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `fullname` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `phone` varchar(50) NOT NULL,
        `location_address` varchar(255) DEFAULT NULL,
        `latitude` decimal(10,7) DEFAULT NULL,
        `longitude` decimal(10,7) DEFAULT NULL,
        `preferences` text,
        `consent_text` text,
        `consent_timestamp` datetime DEFAULT NULL,
        `buyer_agent` varchar(255) DEFAULT NULL,
        `terms_accepted` tinyint(1) DEFAULT 0,
        `accepted_at` datetime DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`buyer_id`),
        UNIQUE KEY `user_id` (`user_id`),
        CONSTRAINT `fk_buyer_user` FOREIGN KEY (`user_id`) REFERENCES `credentialss` (`ids`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Insert or update buyer
    $stmt = $conn->prepare("INSERT INTO buyers 
        (user_id, fullname, email, phone, location_address, latitude, longitude, preferences, consent_text, consent_timestamp, buyer_agent, terms_accepted, accepted_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1, NOW())
        ON DUPLICATE KEY UPDATE
        fullname = VALUES(fullname),
        email = VALUES(email),
        phone = VALUES(phone),
        location_address = VALUES(location_address),
        latitude = VALUES(latitude),
        longitude = VALUES(longitude),
        preferences = VALUES(preferences),
        buyer_agent = VALUES(buyer_agent),
        updated_at = NOW()");
    
    $stmt->bind_param("issssddsss", $user_id, $fullname, $email, $phone, $location_address, $latitude, $longitude, $preferences, $consent_text, $buyer_agent);
    
    if (!$stmt->execute()) {
        error_log("saveBuyer error: " . $stmt->error);
        send_json(['success' => false, 'error' => 'Could not save buyer. Make sure user_id ' . $user_id . ' exists in credentialss.']);
    }
    
    $buyer_id = $stmt->insert_id;
    if ($buyer_id == 0) {
        $res = $conn->query("SELECT buyer_id FROM buyers WHERE user_id = $user_id");
        if ($row = $res->fetch_assoc()) $buyer_id = $row['buyer_id'];
    }
    $stmt->close();

    // Consent log
    $conn->query("CREATE TABLE IF NOT EXISTS `buyer_consent_logs` (
        `log_id` int(11) NOT NULL AUTO_INCREMENT,
        `buyer_id` int(11) NOT NULL,
        `consent_text` text,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` varchar(255) DEFAULT NULL,
        `given_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`log_id`)
    ) ENGINE=InnoDB");
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $log = $conn->prepare("INSERT INTO buyer_consent_logs (buyer_id, consent_text, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $log->bind_param("isss", $buyer_id, $consent_text, $ip, $buyer_agent);
    $log->execute();
    $log->close();

    $_SESSION['buyer_id'] = $buyer_id;
    send_json(['success' => true, 'buyer_id' => $buyer_id, 'user_id' => $user_id]);
}

// ---------- other actions (getNearSellers, getSellerInfo, sendMessage) remain the same ----------
// ... (copy from previous working version, they already use $_SESSION for user checks)
// For brevity, keep the rest unchanged from the robust version I gave earlier.
// Make sure they also check $_SESSION['user_id'] for authorization.

// Default fallback
send_json(['success' => false, 'error' => 'Invalid action']);
?>