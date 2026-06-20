<?php
session_start();
require_once __DIR__ . '/encryption.php';   // must be before any saveEntry use
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/sell_errors.log');
ob_clean();

$conn = new mysqli('localhost', 'root', '', 'Chickacc');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}



$action = $_GET['action'] ?? $_POST['action'] ?? '';


// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF token for all POST actions except read‑only
$read_only_actions = ['getSession', 'getEntries', 'getPhoto', 'getNotifications', 'getInquiryMessages'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, $read_only_actions)) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }
}

// ---------- GET SESSION ----------
if ($action === 'getSession') {
    $user_id = $_SESSION['user_id'] ?? null;
    echo json_encode(['success' => true, 'user_id' => $user_id]);
    exit;
}

// ---------- LOGIN (set session) ----------
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

// ---------- GET ENTRIES (seller's listings) ----------
if ($action === 'getEntries') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];

    // Select all needed fields, including encrypted coords
    $stmt = $conn->prepare("SELECT location_id, description, socmed, number, location_address,
                                   exact_lat_encrypted, exact_lng_encrypted, 
                                   photo_name, saved_at
                            FROM locations 
                            WHERE user_id = ?
                            ORDER BY saved_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Default fuzzy values = null
        $row['fuzzy_lat'] = null;
        $row['fuzzy_lng'] = null;

        // Decrypt and fuzzify if we have encrypted data
        if (!is_null($row['exact_lat_encrypted']) && !is_null($row['exact_lng_encrypted'])) {
            try {
                $exactLat = decryptCoordinate($row['exact_lat_encrypted']);
                $exactLng = decryptCoordinate($row['exact_lng_encrypted']);
                $fuzzy = getFuzzyCoords($exactLat, $exactLng);
                $row['fuzzy_lat'] = $fuzzy['lat'];
                $row['fuzzy_lng'] = $fuzzy['lng'];
            } catch (\Exception $e) {
                // Leave fuzzy as null
            }
        }
        // Remove encrypted binary from output (it’s huge and useless for frontend)
        unset($row['exact_lat_encrypted'], $row['exact_lng_encrypted']);

        $items[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $items]);
    exit;
}

// ---------- SAVE ENTRY (with photo & location) ----------
if ($action === 'saveEntry') {
    // RA 10173 Sec 12 – Consent already recorded in consent_text

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

    $socmed       = trim($_POST['socmed'] ?? '');
    $number       = trim($_POST['number'] ?? '');
    $location_address = trim($_POST['location_address'] ?? '');
    $latitude_raw = !empty($_POST['latitude'])  ? (float)$_POST['latitude'] : null;
    $longitude_raw = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $consent_text = trim($_POST['consent_text'] ?? 'Consent given');
    $device_info  = trim($_POST['device_info'] ?? '');

    // ---------- ENCRYPT EXACT COORDINATES (RA 10173 Sec 20) ----------
    $exact_lat_encrypted = null;
    $exact_lng_encrypted = null;
    if ($latitude_raw !== null && $longitude_raw !== null) {
        try {
            $exact_lat_encrypted = encryptCoordinate($latitude_raw);
            $exact_lng_encrypted = encryptCoordinate($longitude_raw);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Encryption error: ' . $e->getMessage()]);
            exit;
        }
    }

    // Retention: 30 days from now (RA 10173 Sec 11e – Storage Limitation)
    $retention_until = date('Y-m-d H:i:s', strtotime('+30 days'));
    $is_business_address = 0; // default false

    // ---------- Enhanced image handling (unchanged) ----------
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

        // 1. Reject overly large uploads early (max 5 MB encoded string)
        if (strlen($base64) > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'Image too large. Max size 5 MB.']);
            exit;
        }

        // 2. Validate image signature (magic bytes)
        $allowed_signatures = [
            'jpeg' => "\xFF\xD8\xFF",
            'png'  => "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A",
            'gif'  => ['GIF87a', 'GIF89a']
        ];
        $valid = false;
        if (substr($image_data, 0, 3) === $allowed_signatures['jpeg']) {
            $valid = true;
        } elseif (substr($image_data, 0, 8) === $allowed_signatures['png']) {
            $valid = true;
        } else {
            $gif_header = substr($image_data, 0, 6);
            if ($gif_header === $allowed_signatures['gif'][0] || $gif_header === $allowed_signatures['gif'][1]) {
                $valid = true;
            }
        }
        if (!$valid) {
            echo json_encode(['success' => false, 'error' => 'Invalid image format. Only JPEG, PNG, GIF allowed.']);
            exit;
        }

        // 3. GD library check
        if (!extension_loaded('gd')) {
            echo json_encode(['success' => false, 'error' => 'GD library not installed']);
            exit;
        }

        // 4. Create image from string
        $source = @imagecreatefromstring($image_data);
        if (!$source) {
            echo json_encode(['success' => false, 'error' => 'Unsupported or corrupted image.']);
            exit;
        }

        // 5. Convert to truecolor if needed
        if (!imageistruecolor($source)) {
            $width  = imagesx($source);
            $height = imagesy($source);
            $truecolor = imagecreatetruecolor($width, $height);
            imagecopy($truecolor, $source, 0, 0, 0, 0, $width, $height);
            imagedestroy($source);
            $source = $truecolor;
        }

        // 6. Resize to max 1200px
        $max_dim = 1200;
        $orig_width  = imagesx($source);
        $orig_height = imagesy($source);
        if ($orig_width > $max_dim || $orig_height > $max_dim) {
            $scale = $max_dim / max($orig_width, $orig_height);
            $new_width  = (int)($orig_width * $scale);
            $new_height = (int)($orig_height * $scale);
            $resized = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
            imagedestroy($source);
            $source = $resized;
        }

        // 7. Compress to JPEG under 100KB
        $target_size = 100 * 1024;
        $quality = 70;
        $compressed = null;
        for ($i = 0; $i < 10; $i++) {
            ob_start();
            imagejpeg($source, null, $quality);
            $compressed = ob_get_clean();
            if (strlen($compressed) <= $target_size) break;
            $quality -= 5;
            if ($quality < 15) $quality = 15;
        }
        imagedestroy($source);

        if ($compressed === null || strlen($compressed) > $target_size) {
            echo json_encode(['success' => false, 'error' => 'Image too large to compress under 100KB. Please upload a smaller image.']);
            exit;
        }

        // 8. Final JPEG signature check
        if (substr($compressed, 0, 3) !== "\xFF\xD8\xFF") {
            echo json_encode(['success' => false, 'error' => 'Image compression failed.']);
            exit;
        }

        $photo_data = $compressed;
        $photo_name = 'img_' . time() . '_' . rand(100, 999) . '.jpg';
    }

    // ---------- DATABASE INSERT ----------
    $stmt = $conn->prepare("INSERT INTO locations 
        (user_id, description, socmed, number, location_address, 
         exact_lat_encrypted, exact_lng_encrypted, retention_until, is_business_address, 
         photo, photo_name, consent_text, device_info, saved_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    // Bind parameters – types: i (int), s (string), b (blob) – but we treat blob as string 's'
    $stmt->bind_param("isssssssissss",
        $user_id,              // i
        $description,          // s
        $socmed,               // s
        $number,               // s
        $location_address,     // s
        $exact_lat_encrypted,  // s (binary string)
        $exact_lng_encrypted,  // s
        $retention_until,      // s
        $is_business_address,  // i
        $photo_data,           // s (blob)
        $photo_name,           // s
        $consent_text,         // s
        $device_info           // s
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'location_id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ---------- DELETE ENTRY ----------
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
    echo json_encode(['success' => $stmt->affected_rows > 0]);
    $stmt->close();
    exit;
}

// ---------- GET PHOTO ----------
if ($action === 'getPhoto') {
    if (empty($_SESSION['user_id'])) {
        http_response_code(403);
        exit;
    }
    $location_id = (int)($_GET['location_id'] ?? 0);
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT photo, photo_name FROM locations WHERE location_id = ? AND user_id = ? AND photo IS NOT NULL");
    $stmt->bind_param("ii", $location_id, $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($photo_data, $photo_name);
    if ($stmt->fetch() && !is_null($photo_data)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $photo_data);
        finfo_close($finfo);
        header('Content-Type: ' . ($mime ?: 'image/jpeg'));
        echo $photo_data;
    } else {
        http_response_code(404);
    }
    $stmt->close();
    exit;
}

// ---------- GET NOTIFICATIONS (seller's inquiries) ----------
if ($action === 'getNotifications') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $seller_id = (int)$_SESSION['user_id'];

    $sql = "SELECT n.notif_id, n.buyer_id, n.inquiry_id, n.message_summary, n.is_read, n.created_at,
                   b.user_id AS buyer_user_id, c.User AS buyer_username,
                   b.fullname AS buyer_name, b.email AS buyer_email, b.phone AS buyer_phone,
                   li.description AS listing_desc
            FROM seller_notifications n
            LEFT JOIN buyers b ON b.buyer_id = n.buyer_id
            LEFT JOIN credentialss c ON c.ids = b.user_id
            LEFT JOIN buyer_inquiries bi ON bi.inquiry_id = n.inquiry_id
            LEFT JOIN locations li ON li.location_id = bi.location_id
            WHERE n.seller_id = ? AND (n.is_read = 0 OR n.is_read IS NULL)
            ORDER BY n.created_at DESC
            LIMIT 200";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    // Verify inquiry belongs to this seller
    $inq = $conn->query("SELECT seller_id, user_id, profile_id FROM buyer_inquiries WHERE inquiry_id = $inquiry_id LIMIT 1");
    if (!$inq || $inq->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => 'Inquiry not found']);
        exit;
    }
    $row = $inq->fetch_assoc();
    if ((int)$row['seller_id'] !== $seller_id) {
        echo json_encode(['success' => false, 'error' => 'Not authorized']);
        exit;
    }
    $buyer_user_id = (int)$row['user_id'];
    $profile_id = (int)$row['profile_id'];

    // Fetch messages
    $stmt = $conn->prepare("SELECT message_id, sender_type, message_content, sent_at FROM buyer_seller_messages WHERE inquiry_id = ? ORDER BY sent_at ASC");
    $stmt->bind_param('i', $inquiry_id);
    $stmt->execute();
    $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get buyer profile info
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

// ---------- DISMISS NOTIFICATION ----------
if ($action === 'dismissNotification') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $seller_id = (int)$_SESSION['user_id'];
    $notif_id = (int)($_POST['notif_id'] ?? 0);
    if (!$notif_id) {
        echo json_encode(['success' => false, 'error' => 'Missing notif_id']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE seller_notifications SET is_read = 1 WHERE notif_id = ? AND seller_id = ?");
    $stmt->bind_param('ii', $notif_id, $seller_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
$conn->close();
?>