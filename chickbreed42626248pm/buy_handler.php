<?php
// buy_handler.php – uses user_id from credentialss, no buyer_id
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/buy_errors.log');
ob_clean();
mysqli_report(MYSQLI_REPORT_OFF);

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

// ---------- getSession ----------
if ($action === 'getSession') {
    $uid = $_SESSION['user_id'] ?? 0;
    if ($uid > 0) {
        send_json(['success' => true, 'user_id' => $uid]);
    } else {
        send_json(['success' => false, 'error' => 'Not logged in']);
    }
}

// ---------- saveBuyer (creates/updates buyers table) ----------
if ($action === 'saveBuyer') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
        send_json(['success' => false, 'error' => 'You must log in first']);
    }
    $user_id = (int)$_SESSION['user_id'];
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

    // Create buyers table if missing
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
        send_json(['success' => false, 'error' => 'Could not save buyer: ' . $stmt->error]);
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

// ---------- getBuyer ----------
if ($action === 'getBuyer') {
    if (empty($_SESSION['user_id'])) send_json(['success' => false, 'error' => 'Unauthorized']);
    $user_id = (int)$_SESSION['user_id'];
    $res = $conn->query("SELECT buyer_id, user_id, fullname, email, phone, location_address, latitude, longitude, preferences, created_at FROM buyers WHERE user_id = $user_id LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        $_SESSION['buyer_id'] = (int)$row['buyer_id'];
        send_json(['success' => true, 'buyer' => $row]);
    } else {
        send_json(['success' => false, 'error' => 'Buyer not found']);
    }
}

// ---------- BUYER PROFILES (unchanged) ----------
if ($action === 'saveBuyerProfile') {
    if (empty($_SESSION['user_id'])) send_json(['success' => false, 'error' => 'Unauthorized']);
    $user_id = (int)$_SESSION['user_id'];

    $conn->query("CREATE TABLE IF NOT EXISTS buyer_profiles (
        profile_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        fullname VARCHAR(150) NOT NULL,
        email VARCHAR(150) DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        preferences TEXT,
        location_address VARCHAR(255) DEFAULT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $profile_id = (int)($_POST['profile_id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $preferences = trim($_POST['preferences'] ?? '');
    $location_address = trim($_POST['location_address'] ?? '');
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

    if (!$fullname) send_json(['success' => false, 'error' => 'Full name is required']);

    if ($profile_id) {
        $stmt = $conn->prepare("UPDATE buyer_profiles SET fullname=?, email=?, phone=?, preferences=?, location_address=?, latitude=?, longitude=? WHERE profile_id=? AND user_id=?");
        if (!$stmt) send_json(['success' => false, 'error' => 'Query prepare failed (update profile): ' . $conn->error]);
        $stmt->bind_param('sssssddii', $fullname, $email, $phone, $preferences, $location_address, $latitude, $longitude, $profile_id, $user_id);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) send_json(['success' => true, 'profile_id' => $profile_id]);
        send_json(['success' => false, 'error' => 'Could not update profile']);
    } else {
        $stmt = $conn->prepare("INSERT INTO buyer_profiles (user_id, fullname, email, phone, preferences, location_address, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) send_json(['success' => false, 'error' => 'Query prepare failed (insert profile): ' . $conn->error]);
        $stmt->bind_param('isssssdd', $user_id, $fullname, $email, $phone, $preferences, $location_address, $latitude, $longitude);
        $ok = $stmt->execute();
        $pid = $stmt->insert_id;
        $stmt->close();
        if ($ok) send_json(['success' => true, 'profile_id' => $pid]);
        send_json(['success' => false, 'error' => 'Could not create profile']);
    }
}

if ($action === 'getBuyerProfiles') {
    if (empty($_SESSION['user_id'])) send_json(['success' => false, 'error' => 'Unauthorized']);
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT profile_id, fullname, email, phone, preferences, location_address, latitude, longitude, created_at, updated_at FROM buyer_profiles WHERE user_id = ? ORDER BY updated_at DESC");
    if (!$stmt) send_json(['success' => false, 'error' => 'Query prepare failed (get profiles): ' . $conn->error]);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    send_json(['success' => true, 'data' => $rows]);
}

if ($action === 'getBuyerProfile') {
    if (empty($_SESSION['user_id'])) send_json(['success' => false, 'error' => 'Unauthorized']);
    $pid = (int)($_POST['profile_id'] ?? 0);
    if (!$pid) send_json(['success' => false, 'error' => 'Missing profile_id']);
    $stmt = $conn->prepare("SELECT profile_id, fullname, email, phone, preferences, location_address, latitude, longitude, created_at, updated_at FROM buyer_profiles WHERE profile_id = ? AND user_id = ?");
    if (!$stmt) send_json(['success' => false, 'error' => 'Query prepare failed (get profile): ' . $conn->error]);
    $stmt->bind_param('ii', $pid, $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) send_json(['success' => true, 'profile' => $row]);
    send_json(['success' => false, 'error' => 'Profile not found']);
}

if ($action === 'deleteBuyerProfile') {
    if (empty($_SESSION['user_id'])) send_json(['success' => false, 'error' => 'Unauthorized']);
    $user_id = (int)$_SESSION['user_id'];
    $pid = (int)($_POST['profile_id'] ?? 0);
    if (!$pid) send_json(['success' => false, 'error' => 'Missing profile_id']);
    $stmt = $conn->prepare("DELETE FROM buyer_profiles WHERE profile_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $pid, $user_id);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    send_json(['success' => $ok]);
}

// ---------- GET BUYER INQUIRIES ----------
if ($action === 'getBuyerInquiries') {
    if (empty($_SESSION['user_id'])) send_json(['success' => false, 'error' => 'Unauthorized']);
    $user_id = (int)$_SESSION['user_id'];

    $sql = "SELECT bi.inquiry_id, bi.location_id, bi.seller_id, bi.inquiry_status, bi.interested_at,
                   s.User AS seller_name, l.description AS listing_desc, bp.fullname AS profile_name
            FROM buyer_inquiries bi
            LEFT JOIN credentialss s ON s.ids = bi.seller_id
            LEFT JOIN locations l ON l.location_id = bi.location_id
            LEFT JOIN buyer_profiles bp ON bp.profile_id = bi.profile_id
            WHERE bi.user_id = ?
            ORDER BY bi.interested_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) send_json(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    send_json(['success' => true, 'data' => $rows]);
}

// ---------- GET SELLER INFO (now fully sanitized – NO coordinates exposed) ----------
if ($action === 'getSellerInfo') {
    if (empty($_SESSION['user_id'])) send_json(['success' => false, 'error' => 'Unauthorized']);
    $seller_id = (int)($_POST['seller_id'] ?? 0);
    if (!$seller_id) send_json(['success' => false, 'error' => 'Missing seller_id']);

    $sellerRow = $conn->query("SELECT ids, User FROM credentialss WHERE ids = $seller_id LIMIT 1");
    if (!$sellerRow || !($seller = $sellerRow->fetch_assoc())) send_json(['success' => false, 'error' => 'Seller not found']);

    // Only fetch non‑sensitive listing data – no latitude/longitude/encrypted columns
    $stmt = $conn->prepare("SELECT location_id, description, socmed, number, location_address, saved_at FROM locations WHERE user_id = ? ORDER BY saved_at DESC LIMIT 100");
    if (!$stmt) send_json(['success' => false, 'error' => 'Query prepare failed']);
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Extra safety: remove any accidentally included sensitive fields
    foreach ($listings as &$l) {
        unset($l['latitude'], $l['longitude'], $l['exact_lat_encrypted'], $l['exact_lng_encrypted']);
    }
    send_json(['success' => true, 'seller' => $seller, 'listings' => $listings]);
}

// ---------- SEND MESSAGE (unchanged, keep your existing block) ----------
if ($action === 'sendMessage') {
    // ... paste your entire sendMessage block here exactly as it was ...
    // It's long but already correct; I'm omitting it only to save space.
    // Make sure you include it unchanged!
}

// Default fallback
send_json(['success' => false, 'error' => 'Invalid action']);