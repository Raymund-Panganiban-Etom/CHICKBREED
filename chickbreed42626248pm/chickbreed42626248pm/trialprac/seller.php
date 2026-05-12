<?php
// seller.php - seller posts listing with location consent
session_start();
require 'db.php';
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
  header('Location: login.php'); exit;
}
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $desc  = trim($_POST['description'] ?? '');
  $lat   = isset($_POST['lat']) && $_POST['lat'] !== '' ? floatval($_POST['lat']) : null;
  $lon   = isset($_POST['lon']) && $_POST['lon'] !== '' ? floatval($_POST['lon']) : null;
  $consent = ($_POST['consent'] ?? '') === '1';
  if ($title === '') $errors[] = 'Title required.';
  if (!$consent) $errors[] = 'Consent required to attach location.';
  if (empty($errors)) {
    $stmt = $pdo->prepare("INSERT INTO listings (seller_id, title, description, lat, lon) VALUES (:sid,:t,:d,:lat,:lon)");
    $stmt->execute([
      ':sid' => $_SESSION['user_id'],
      ':t' => $title,
      ':d' => $desc,
      ':lat' => $lat,
      ':lon' => $lon
    ]);
    $success = 'Listing saved.';
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Seller Dashboard</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>body{font-family:Arial;padding:18px} label{display:block;margin-top:8px}</style>
</head>
<body>
  <h2>Seller — Post Listing</h2>
  <?php if ($errors): foreach($errors as $e) echo "<div style='color:red'>".htmlspecialchars($e)."</div>"; endif; ?>
  <?php if ($success): ?><div style="color:green"><?=htmlspecialchars($success)?></div><?php endif; ?>

  <form method="post" id="form">
    <label>Title<br><input name="title" required></label>
    <label>Description<br><textarea name="description"></textarea></label>

    <input type="hidden" name="lat" id="lat">
    <input type="hidden" name="lon" id="lon">
    <input type="hidden" name="consent" id="consent">

    <div style="margin-top:8px">
      <button type="button" id="locBtn">Request Location</button>
      <button type="submit">Submit Listing</button>
    </div>
  </form>

  <p><a href="seller_messages.php">View messages</a> | <a href="logout.php">Logout</a></p>

  <div id="map" style="height:300px;margin-top:12px;display:none"></div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const locBtn = document.getElementById('locBtn');
    const consentInput = document.getElementById('consent');
    const latInput = document.getElementById('lat');
    const lonInput = document.getElementById('lon');
    const mapDiv = document.getElementById('map');
    let map, marker;

    locBtn.addEventListener('click', ()=> {
      if (!confirm('Do you accept sharing your location for this listing?')) return;
      consentInput.value = '1';
      if (!navigator.geolocation) { alert('Geolocation not supported'); return; }
      navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        latInput.value = lat;
        lonInput.value = lon;
        mapDiv.style.display = 'block';
        if (!map) {
          map = L.map('map').setView([lat, lon], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        } else map.setView([lat, lon], 15);
        if (marker) marker.remove();
        marker = L.marker([lat, lon], {draggable:true}).addTo(map).bindPopup('Your listing location').openPopup();
        marker.on('dragend', ()=> {
          const p = marker.getLatLng();
          latInput.value = p.lat.toFixed(6);
          lonInput.value = p.lng.toFixed(6);
        });
        alert('Location captured. You can submit the listing now.');
      }, err => {
        alert('Unable to get location. You can still submit but consent is required to attach coordinates.');
      }, {enableHighAccuracy:true, timeout:10000});
    });
  </script>
</body>
</html>
