<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
header('Content-Type: text/html; charset=utf-8');
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    // If not logged in, redirect to login page (adjust path)
    header('Location: login.php');
    exit;
}
$logged_user_id = (int)$_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=yes" />
<title>Seller Listings – Chickbreed</title>
<link rel="stylesheet" href="sell.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

</head>
<body>
<div class="container">
  <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="plus-box" id="plusBox" title="Add new item">+</div>
    <div>
      <h2 style="margin:0">Your Items</h2>
      <div class="small">Click the plus to add a new entry. Entries are stored with your consent.</div>
    </div>

 <a href="home.php" class="btn" style="background:#F9A825; color:#5D2906; text-decoration:none; margin-left:auto;"><i class="fas fa-home"></i> Back to Home</a>
   

  </div>

  <div class="form-card" id="formCard" aria-hidden="true">
    <form id="entryForm">
      <label>Description <span style="font-weight:400;color:var(--muted)">(what do you sell, it will see by potential buyers)</span></label>
      <textarea name="description" id="description" required></textarea>
      <div class="row">
        <div>
          <label>Photo <span style="font-weight:400;color:var(--muted)">(optional)</span></label>
          <input type="file" id="photo" accept="image/*">
          <div class="small">Small images recommended, less than 1mb, use image compressor. <a href="https://imagecompressor.com/" target="_blank" rel="noopener noreferrer">Click Here</a></div>
        </div>
        <div>
          <label>Social Media</label>
          <input type="text" id="socmed" placeholder="Fb, titok, or ig to contact you">
          <label style="margin-top:8px">Number</label>
          <input type="tel" id="number" placeholder="+63...">
        </div>
      </div>
      <label>Location</label>
      <input type="text" id="location" placeholder="Type a location or address">
      <div id="locationHelp" class="small">Location will be auto-captured after consent acceptance.</div>
      <div style="display:flex;gap:8px;align-items:center;">
        <button type="button" class="btn" id="submitBtn">Submit</button>
        <button type="button" class="btn secondary" id="cancelBtn">Cancel</button>
      </div>
    </form>
  </div>

  <div id="list" aria-live="polite"></div>

  <div id="notificationsSection" style="margin-top:24px; display:none;">
    <h3>Buyer Inquiries & Notifications</h3>
    <div id="notificationsList">Loading notifications...</div>
  </div>
</div>

<!-- Consent modal (unchanged) -->
<div class="modal" id="consentModal" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="modal-card">
    <h3 style="margin-top:0">Consent under Republic Act No. 10173</h3>
    <div class="consent-text" id="consentText">
      By clicking <strong>Accept</strong> you give your explicit consent to the collection and processing of the personal data you provided (including photo and location) for the purpose of recording this entry. Your location will be automatically captured from your device. This consent is recorded in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173). You may request access, correction, or deletion of your data.
    </div>
    <label style="display:block;margin-top:10px"><input type="checkbox" id="consentCheck"> I have read and accept the terms and privacy statement above</label>
    <div id="locationStatus" class="location-status" style="display:none;"></div>
    <div class="map-container" id="mapContainer"><div id="mapView"></div></div>
    <button type="button" class="map-toggle" id="mapToggle" style="display:none;">Show Map</button>
    <div class="map-info" id="mapInfo" style="display:none;">
      <p><strong>Captured Location:</strong></p>
      <p>Latitude: <span id="coordsLat">-</span></p>
      <p>Longitude: <span id="coordsLon">-</span></p>
      <p><em>Map shows your device's GPS location</em></p>
    </div>
    <div class="consent-actions">
      <button class="btn secondary" id="consentCancel">Cancel</button>
      <button class="btn" id="consentAccept">Accept</button>
    </div>
    <div class="consent-copy">A copy of this consent text will be saved with the entry in our secure database.</div>
  </div>
</div>

<script src="sell.js"></script>
</body>
</html>