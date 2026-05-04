<?php
// buy.php
// Simple server-side handling for the form (no DB). Adjust paths/permissions for production.
$submission = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization
    $description = trim($_POST['description'] ?? '');
    $buyer_name  = trim($_POST['buyer_name'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $social      = trim($_POST['social'] ?? '');
    $lat         = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lon         = isset($_POST['lon']) ? floatval($_POST['lon']) : null;
    $consent     = isset($_POST['consent']) ? trim($_POST['consent']) : '';

    // Validate required fields
    if ($description === '') $errors[] = 'Description is required.';
    if ($buyer_name === '') $errors[] = 'Buyer name is required.';
    if ($consent !== '1') $errors[] = 'Consent is required to submit.';

    // Handle photo upload (optional)
    $photo_path = null;
    if (!empty($_FILES['photo']['name'])) {
        $f = $_FILES['photo'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            if (!array_key_exists($mime, $allowed)) {
                $errors[] = 'Photo must be JPG, PNG, or GIF.';
            } elseif ($f['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Photo must be smaller than 5MB.';
            } else {
                $ext = $allowed[$mime];
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $safeName = bin2hex(random_bytes(12)) . '.' . $ext;
                $dest = $uploadDir . $safeName;
                if (move_uploaded_file($f['tmp_name'], $dest)) {
                    $photo_path = 'uploads/' . $safeName;
                } else {
                    $errors[] = 'Failed to save uploaded photo.';
                }
            }
        } else {
            $errors[] = 'Photo upload error.';
        }
    }

    if (empty($errors)) {
        // Build submission summary (no DB)
        $submission = [
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'buyer_name'  => htmlspecialchars($buyer_name, ENT_QUOTES, 'UTF-8'),
            'address'     => htmlspecialchars($address, ENT_QUOTES, 'UTF-8'),
            'phone'       => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
            'email'       => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
            'social'      => htmlspecialchars($social, ENT_QUOTES, 'UTF-8'),
            'lat'         => $lat,
            'lon'         => $lon,
            'photo'       => $photo_path,
            'saved_at'    => date('c')
        ];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Buy</title>

  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

  <style>
    *{box-sizing:border-box}
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f7fb;margin:0;padding:28px;display:flex;justify-content:center}
    .card{width:420px;background:#fff;border-radius:12px;padding:18px;box-shadow:0 8px 30px rgba(10,20,40,.06)}
    h1{font-size:20px;margin:0 0 8px}
    form .row{display:flex;gap:8px}
    label{display:block;font-weight:600;margin-top:10px;font-size:13px}
    input[type="text"], input[type="email"], input[type="number"], textarea{width:100%;padding:8px;border:1px solid #e3e8ef;border-radius:8px;margin-top:6px;font-size:14px}
    textarea{min-height:70px;resize:vertical}
    .small{font-size:12px;color:#6b7280;margin-top:6px}
    .btn{display:inline-block;background:#0b78d1;color:#fff;padding:10px 14px;border-radius:8px;border:0;cursor:pointer;margin-top:12px}
    .btn.ghost{background:#eef6ff;color:#0b78d1;border:1px solid #d6e9ff}
    #modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);align-items:center;justify-content:center;z-index:40}
    .modal-card{background:#fff;padding:18px;border-radius:10px;width:360px;max-width:94%}
    .modal-card p{margin:0 0 12px}
    #map{height:260px;margin-top:12px;border-radius:8px;border:1px solid #e6eef8;display:none}
    .errors{background:#fff3f3;border:1px solid #ffd6d6;color:#8b1d1d;padding:10px;border-radius:8px;margin-bottom:10px}
    .success{background:#f0fdf4;border:1px solid #b7f5c1;color:#0b6b2b;padding:10px;border-radius:8px;margin-bottom:10px}
    .summary img{max-width:100%;border-radius:8px;margin-top:8px}
    @media (max-width:480px){ .card{width:100%;padding:12px} }
  </style>
</head>
<body>
  <div class="card" role="main">
    <h1>Buy — Provide your details</h1>

    <?php if (!empty($errors)): ?>
      <div class="errors">
        <strong>There were problems with your submission:</strong>
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($submission): ?>
      <div class="success">
        <strong>Submission saved locally on server.</strong>
        <div class="small">Saved at <?= htmlspecialchars($submission['saved_at'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>

      <div class="summary">
        <p><strong>Description:</strong> <?= $submission['description'] ?></p>
        <p><strong>Buyer:</strong> <?= $submission['buyer_name'] ?></p>
        <p><strong>Address:</strong> <?= $submission['address'] ?></p>
        <p><strong>Phone:</strong> <?= $submission['phone'] ?> &nbsp; <strong>Email:</strong> <?= $submission['email'] ?></p>
        <p><strong>Social:</strong> <?= $submission['social'] ?></p>
        <?php if ($submission['photo']): ?>
          <p><strong>Photo:</strong><br><img src="<?= htmlspecialchars($submission['photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Uploaded photo"></p>
        <?php endif; ?>
        <?php if ($submission['lat'] !== null && $submission['lon'] !== null): ?>
          <p><strong>Coordinates:</strong> <?= $submission['lat'] ?>, <?= $submission['lon'] ?></p>
          <div id="confirmMap" style="height:220px;border-radius:8px;margin-top:8px"></div>
        <?php endif; ?>
      </div>
      <hr>
    <?php endif; ?>

    <form id="buyForm" method="POST" enctype="multipart/form-data" novalidate>
      <label for="description">Buyer Description</label>
      <textarea id="description" name="description" required><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

      <label for="buyer_name">Buyer Name</label>
      <input id="buyer_name" name="buyer_name" type="text" value="<?= htmlspecialchars($_POST['buyer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

      <label for="address">Address</label>
      <input id="address" name="address" type="text" value="<?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <div class="row">
        <div style="flex:1">
          <label for="phone">Phone</label>
          <input id="phone" name="phone" type="text" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div style="flex:1">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <label for="social">Fb, X or Instagram to contact</label>
      <input id="social" name="social" type="text" value="<?= htmlspecialchars($_POST['social'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <label for="photo">Photo (optional)</label>
      <input id="photo" name="photo" type="file" accept="image/*">

      <!-- Hidden fields for coordinates and consent -->
      <input type="hidden" name="lat" id="lat" value="<?= htmlspecialchars($_POST['lat'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="lon" id="lon" value="<?= htmlspecialchars($_POST['lon'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="consent" id="consent" value="<?= htmlspecialchars($_POST['consent'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <div class="small">Click the button below to open the consent dialog and allow location access (optional). If you accept, your coordinates will be attached to the submission.</div>

      <div style="display:flex;gap:8px;margin-top:12px;align-items:center">
        <button type="button" class="btn" id="openBtn">Request Location</button>
        <button type="submit" class="btn ghost">Submit Form</button>
      </div>

      <div id="map" aria-hidden="true"></div>
    </form>
  </div>

  <!-- Consent Modal -->
  <div id="modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <h2 id="modalTitle" style="margin-top:0;font-size:16px">Consent to share location</h2>
      <p>
        By clicking <strong>Accept</strong> you agree to share your device location for the purpose of associating coordinates with this submission.
        Your explicit consent will be recorded in the submission. You may decline and still submit the form without location.
      </p>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button id="declineBtn" class="btn ghost">Decline</button>
        <button id="acceptBtn" class="btn">Accept</button>
      </div>
    </div>
  </div>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    // Modal and geolocation logic
    const openBtn = document.getElementById('openBtn');
    const modal = document.getElementById('modal');
    const acceptBtn = document.getElementById('acceptBtn');
    const declineBtn = document.getElementById('declineBtn');
    const outputMap = document.getElementById('map');
    const latInput = document.getElementById('lat');
    const lonInput = document.getElementById('lon');
    const consentInput = document.getElementById('consent');
    let map, marker;

    openBtn.addEventListener('click', () => {
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');
    });

    declineBtn.addEventListener('click', () => {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      // ensure consent cleared
      consentInput.value = '';
    });

    acceptBtn.addEventListener('click', () => {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      // set consent flag so server knows user accepted
      consentInput.value = '1';

      if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
      }

      // show map container
      outputMap.style.display = 'block';
      outputMap.setAttribute('aria-hidden', 'false');

      navigator.geolocation.getCurrentPosition(success, error, { enableHighAccuracy: true, timeout: 10000 });

      function success(pos) {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        latInput.value = lat;
        lonInput.value = lon;

        // initialize map if needed
        if (!map) {
          map = L.map('map').setView([lat, lon], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
          }).addTo(map);
        } else {
          map.setView([lat, lon], 15);
        }

        if (marker) marker.remove();
        marker = L.marker([lat, lon], {draggable: true}).addTo(map)
          .bindPopup('Your location (drag to adjust)').openPopup();

        // update hidden inputs if marker dragged
        marker.on('dragend', function(e) {
          const p = e.target.getLatLng();
          latInput.value = p.lat.toFixed(6);
          lonInput.value = p.lng.toFixed(6);
        });
      }

      function error(err) {
        console.warn('Geolocation error', err);
        alert('Unable to retrieve location. You can still submit the form without coordinates.');
      }
    });

    // If the page shows a confirmed submission with coordinates, render a small map
    <?php if ($submission && $submission['lat'] !== null && $submission['lon'] !== null): ?>
    (function(){
      const lat = <?= json_encode($submission['lat']) ?>;
      const lon = <?= json_encode($submission['lon']) ?>;
      const confirmMap = L.map('confirmMap').setView([lat, lon], 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(confirmMap);
      L.marker([lat, lon]).addTo(confirmMap).bindPopup('Submitted location').openPopup();
    })();
    <?php endif; ?>
  </script>
</body>
</html>
