<?php
session_start();
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

// ---------- SAVE ENTRY (with photo & location) ----------
if ($action === 'saveEntry') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Session expired, please login again']);
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];
    $description = trim($_POST['description'] ?? '');
    if (strlen($description) < 2) {
        echo json_encode(['success' => false, 'error' => 'Description is required (min 2 chars)']);
        exit;
    }
    $socmed = trim($_POST['socmed'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $location_address = trim($_POST['location_address'] ?? '');
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $consent_text = trim($_POST['consent_text'] ?? 'Consent given');
    $device_info = trim($_POST['device_info'] ?? '');

    // Photo processing (optional, lightweight)
    $photo_data = null;
    $photo_name = null;
    if (!empty($_POST['photo_base64'])) {
        $base64 = $_POST['photo_base64'];
        if (preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $base64, $matches)) {
            $imageType = $matches[1];
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $photo_data = base64_decode($base64);
            if ($photo_data && strlen($photo_data) <= 2 * 1024 * 1024) { // max 2MB
                $photo_name = 'img_' . time() . '_' . rand(100,999) . '.' . ($imageType === 'jpg' ? 'jpg' : $imageType);
            } else {
                echo json_encode(['success' => false, 'error' => 'Photo too large (max 2MB)']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid image format (JPEG/PNG only)']);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO locations 
        (user_id, description, socmed, number, location_address, latitude, longitude, photo, photo_name, consent_text, device_info, saved_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    // types: i s s s s d d b s s s
    $stmt->bind_param("issssddbsss", $user_id, $description, $socmed, $number, $location_address, $latitude, $longitude, $photo_data, $photo_name, $consent_text, $device_info);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'location_id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
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
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT photo, photo_name FROM locations WHERE location_id = ? AND user_id = ? AND photo IS NOT NULL");
    $stmt->bind_param("ii", $location_id, $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($photo_data, $photo_name);
    if ($stmt->fetch() && !is_null($photo_data)) {
        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline; filename="' . $photo_name . '"');
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