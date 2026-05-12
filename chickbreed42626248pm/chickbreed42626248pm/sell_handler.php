<?php
session_start();

ini_set('memory_limit', '256M');
ini_set('max_execution_time', 120);
error_reporting(E_ALL);
ini_set('display_errors', 0); // never show errors in JSON
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/sell_errors.log');

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // never show errors in JSON output

$conn = new mysqli('localhost', 'root', '', 'Chickacc');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ---------- GET SESSION (for frontend to check) ----------
if ($action === 'getSession') {
    $user_id = $_SESSION['user_id'] ?? null;
    echo json_encode(['success' => true, 'user_id' => $user_id]);
    exit;
}

// ---------- LOGIN (create session) ----------
if ($action === 'login') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id > 0) {
        $_SESSION['user_id'] = $user_id;
        echo json_encode(['success' => true, 'user_id' => $user_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    }
    exit;
}

// ---------- GET ENTRIES (only for logged-in user) ----------
if ($action === 'getEntries') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT location_id, description, socmed, number, location_address, latitude, longitude, photo_name, saved_at FROM locations WHERE user_id = ? ORDER BY saved_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
    exit;
}

// ---------- GET SELLER NOTIFICATIONS / INQUIRIES ----------
if ($action === 'getNotifications') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $seller_id = (int)$_SESSION['user_id'];

    $sql = "SELECT n.notif_id, n.buyer_id, n.inquiry_id, n.message_summary, n.is_read, n.created_at,
                   c.User AS buyer_username, b.fullname AS buyer_name, b.email AS buyer_email, b.phone AS buyer_phone,
                   li.description AS listing_desc, bi.location_id AS listed_location_id, bi.profile_id
            FROM seller_notifications n
            LEFT JOIN buyers b ON b.buyer_id = n.buyer_id
            LEFT JOIN credentialss c ON c.ids = b.user_id
            LEFT JOIN buyer_inquiries bi ON bi.inquiry_id = n.inquiry_id
            LEFT JOIN locations li ON li.location_id = bi.location_id
            WHERE n.seller_id = ? AND (n.is_read = 0 OR n.is_read IS NULL)
            ORDER BY n.created_at DESC
            LIMIT 200";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
    $stmt->close();
    exit;
}

// ---------- GET INQUIRY MESSAGES (conversation) ----------
if ($action === 'getInquiryMessages') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $seller_id = (int)$_SESSION['user_id'];
    $inquiry_id = (int)($_POST['inquiry_id'] ?? 0);
    if (!$inquiry_id) {
        echo json_encode(['success' => false, 'error' => 'Missing inquiry_id']);
        exit;
    }

    // Verify inquiry belongs to seller
    $inq = $conn->query("SELECT user_id, profile_id FROM buyer_inquiries WHERE inquiry_id = $inquiry_id AND seller_id = $seller_id LIMIT 1");
    if (!$inq || $inq->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => 'Inquiry not found or not yours']);
        exit;
    }
    $row = $inq->fetch_assoc();
    $buyer_user_id = (int)$row['user_id'];
    $profile_id = (int)$row['profile_id'];

    // Get messages
    $stmt = $conn->prepare("SELECT message_id, sender_type, message_content, sent_at FROM buyer_seller_messages WHERE inquiry_id = ? ORDER BY sent_at ASC");
    $stmt->bind_param('i', $inquiry_id);
    $stmt->execute();
    $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get buyer details
    $buyer_info = [];
    if ($profile_id > 0) {
        $prof = $conn->query("SELECT fullname, email, phone FROM buyer_profiles WHERE profile_id = $profile_id LIMIT 1");
        if ($prof && $prof->num_rows) $buyer_info = $prof->fetch_assoc();
    } else {
        $buy = $conn->query("SELECT fullname, email, phone FROM buyers WHERE user_id = $buyer_user_id LIMIT 1");
        if ($buy && $buy->num_rows) $buyer_info = $buy->fetch_assoc();
    }
    $cred = $conn->query("SELECT User FROM credentialss WHERE ids = $buyer_user_id LIMIT 1");
    $buyer_username = $cred ? ($cred->fetch_assoc()['User'] ?? 'Buyer') : 'Buyer';

    $profile = [
        'fullname' => $buyer_info['fullname'] ?? 'N/A',
        'email'    => $buyer_info['email'] ?? '',
        'phone'    => $buyer_info['phone'] ?? '',
        'username' => $buyer_username
    ];

    echo json_encode(['success' => true, 'messages' => $msgs, 'profile' => $profile]);
    exit;
}
// ---------- DISMISS NOTIFICATION (seller) ----------
if ($action === 'dismissNotification') {
    if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit; }
    $seller_id = (int)$_SESSION['user_id'];
    $notif_id = (int)($_POST['notif_id'] ?? 0);
    if (!$notif_id) { echo json_encode(['success'=>false,'error'=>'Missing notif_id']); exit; }

    // ensure column exists (best-effort)
    $conn->query("ALTER TABLE seller_notifications ADD COLUMN IF NOT EXISTS dismissed_by_seller TINYINT(1) DEFAULT 0");

    $stmt = $conn->prepare("UPDATE seller_notifications SET is_read = 1, dismissed_by_seller = 1 WHERE notif_id = ? AND seller_id = ?");
    $stmt->bind_param('ii', $notif_id, $seller_id);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
}

// ---------- RESPOND TO INQUIRY (accept/ignore) ----------
if ($action === 'respondInquiry') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $seller_id = (int)$_SESSION['user_id'];
    $inquiry_id = (int)($_POST['inquiry_id'] ?? 0);
    $response = trim($_POST['response'] ?? '');
    $mode = ($_POST['mode'] ?? 'accept');

    if (!$inquiry_id) {
        echo json_encode(['success' => false, 'error' => 'Missing inquiry_id']);
        exit;
    }

    // Verify ownership and get buyer_user_id
    $inq = $conn->query("SELECT user_id FROM buyer_inquiries WHERE inquiry_id = $inquiry_id AND seller_id = $seller_id LIMIT 1");
    if (!$inq || $inq->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => 'Inquiry not found']);
        exit;
    }

    $new_status = ($mode === 'accept') ? 'contacted' : 'archived';
    $conn->query("UPDATE buyer_inquiries SET inquiry_status = '$new_status', last_interaction = NOW() WHERE inquiry_id = $inquiry_id");

    $reply_msg = $response ?: ($mode === 'accept' ? 'Your inquiry has been accepted.' : 'Your inquiry was not accepted.');
    $msgStmt = $conn->prepare("INSERT INTO buyer_seller_messages (inquiry_id, sender_type, message_content) VALUES (?, 'seller', ?)");
    $msgStmt->bind_param('is', $inquiry_id, $reply_msg);
    $msgStmt->execute();
    $msgStmt->close();

    $conn->query("UPDATE seller_notifications SET is_read = 1 WHERE inquiry_id = $inquiry_id AND seller_id = $seller_id");

    echo json_encode(['success' => true]);
    exit;
}

// ---------- SAVE ENTRY (with photo & location) ----------
if ($action === 'saveEntry') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Session expired']);
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];
    $description = trim($_POST['description'] ?? '');
    if (strlen($description) < 2) {
        echo json_encode(['success' => false, 'error' => 'Description required']);
        exit;
    }
    $socmed = trim($_POST['socmed'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $location_address = trim($_POST['location_address'] ?? '');
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $consent_text = trim($_POST['consent_text'] ?? 'Consent given');
    $device_info = trim($_POST['device_info'] ?? '');

    // ---------- PHOTO PROCESSING (fix) ----------
    // ---------- PHOTO PROCESSING (compress to <100KB) ----------
// ---------- PHOTO PROCESSING (safe compress to <100KB) ----------
// ---------- AGGRESSIVE COMPRESSION (ensures ≤100KB) ----------
$photo_data = null;
$photo_name = null;
if (!empty($_POST['photo_base64'])) {
    $base64 = $_POST['photo_base64'];
    if (strpos($base64, 'base64,') !== false) {
        $base64 = explode('base64,', $base64)[1];
    }
    $image_data = base64_decode($base64);
    if ($image_data === false) {
        echo json_encode(['success' => false, 'error' => 'Invalid image data']);
        exit;
    }

    if (!extension_loaded('gd')) {
        echo json_encode(['success' => false, 'error' => 'GD library not installed']);
        exit;
    }

    $source = @imagecreatefromstring($image_data);
    if (!$source) {
        echo json_encode(['success' => false, 'error' => 'Unsupported image format']);
        exit;
    }

    // Resize to max 1200px (fits any screen, compresses well)
    $max_dim = 1200;
    $orig_width = imagesx($source);
    $orig_height = imagesy($source);
    if ($orig_width > $max_dim || $orig_height > $max_dim) {
        $scale = $max_dim / max($orig_width, $orig_height);
        $new_width = (int)($orig_width * $scale);
        $new_height = (int)($orig_height * $scale);
        $resized = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        imagedestroy($source);
        $source = $resized;
    }

    $target_size = 100 * 1024; // 100KB
    $quality = 70;
    $compressed = null;

    for ($i = 0; $i < 10; $i++) {
        ob_start();
        imagejpeg($source, null, $quality);
        $compressed = ob_get_clean();
        if (strlen($compressed) <= $target_size) {
            break;
        }
        $quality -= 5;
        if ($quality < 15) $quality = 15;
    }
    imagedestroy($source);

    if ($compressed === null || strlen($compressed) > $target_size) {
        echo json_encode(['success' => false, 'error' => 'Image too large to compress under 100KB. Please upload a smaller image (max 2MB, under 1200px).']);
        exit;
    }

    $photo_data = $compressed;
    $photo_name = 'img_' . time() . '_' . rand(100, 999) . '.jpg';
}

    // Ensure the photo column is LONGBLOB
    $conn->query("ALTER TABLE locations MODIFY photo LONGBLOB");

    $stmt = $conn->prepare("INSERT INTO locations 
        (user_id, description, socmed, number, location_address, latitude, longitude, photo, photo_name, consent_text, device_info, saved_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    // Note: 's' works for BLOB when binding by reference
    $stmt->bind_param("issssddssss", 
        $user_id, $description, $socmed, $number, $location_address, 
        $latitude, $longitude, $photo_data, $photo_name, $consent_text, $device_info
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'location_id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'DB error: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ---------- DELETE ENTRY (with ownership check) ----------
if ($action === 'deleteEntry') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $location_id = (int)($_POST['location_id'] ?? 0);
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM locations WHERE location_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $location_id, $user_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    echo json_encode(['success' => $affected > 0, 'error' => $affected ? '' : 'No record found or not owned']);
    exit;
}

// ---------- GET PHOTO (secure, session-based) ----------
if ($action === 'getPhoto') {
    if (empty($_SESSION['user_id'])) {
        http_response_code(403);
        exit;
    }
    $location_id = (int)($_GET['location_id'] ?? 0);
    if (!$location_id) {
        http_response_code(400);
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT photo, photo_name FROM locations WHERE location_id = ? AND user_id = ? AND photo IS NOT NULL");
    $stmt->bind_param("ii", $location_id, $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($photo_data, $photo_name);
    if ($stmt->fetch() && !is_null($photo_data)) {
        // Detect MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $photo_data);
        finfo_close($finfo);
        if (!$mime) $mime = 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($photo_data));
        echo $photo_data;
    } else {
        http_response_code(404);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
$conn->close();
?>