<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
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
<title>Find Sellers Near You – Chickbreed</title>
 <link rel="icon" type="image/png" sizes="32x32" href="favicon.png">
<link rel="stylesheet" href="buy.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

</head>
<body>
<div class="container">
  <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
  <h1>🐔 Find Local Sellers Near You</h1>
  <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
    <a href="home.php" class="btn" style="background:#F9A825; color:#5D2906; text-decoration:none;"><i class="fas fa-home"></i> Back to Home</a>
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
</div>
  <div class="step-indicator">
    <div class="step active" id="step1">1. Your Info</div>
    <div class="step" id="step2">2. Consent</div>
    <div class="step" id="step3">3. Find Sellers</div>
    <div class="step" id="step4">4. Contact</div>
  </div>

  <!-- Step 1 -->
  <div class="section" id="buyerFormSection">
    <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;">
      <h2>Your Information (RA 10173 compliant)</h2>
      <button id="buyerFormCloseBtn" class="btn secondary" style="height:36px;">Close</button>
    </div>
    <form id="buyerForm">
      <label>Full Name *</label>
      <input type="text" id="fullname" required placeholder="Juan Dela Cruz">
      <label>Email *</label>
      <input type="email" id="email" required placeholder="buyer@example.com">
      <label>Phone *</label>
      <input type="tel" id="phone" required placeholder="+63 912 345 6789">
      <label>What are you looking for? *</label>
      <textarea id="preferences" required placeholder="e.g., free-range chickens, eggs, poultry supplies..."></textarea>
      <label>Address (optional)</label>
      <input type="text" id="location" placeholder="Barangay, City">
      <div class="row">
        <button type="button" class="btn" id="captureLocBtn">📍 Capture My GPS Location</button>
        <button type="submit" class="btn success">Continue →</button>
      </div>
      <div id="locationStatus" class="status" style="display:none"></div>
      <div id="coordsDisplay" class="coords-display" style="display:none">
        <p><strong>📍 Captured coordinates:</strong> <span id="capturedLat">-</span>, <span id="capturedLng">-</span></p>
      </div>
    </form>
    <div style="margin-top:12px;">
      <h3>Saved Profiles</h3>
      <div id="profilesList" style="display:flex;gap:8px;flex-direction:column"></div>
      <div class="row" style="margin-top:8px">
        <button class="btn secondary" id="addProfileBtn">+ Add Profile</button>
        <button class="btn" id="refreshProfilesBtn">Refresh Profiles</button>
      </div>
    </div>
  </div>

  <!-- Step 2: Consent -->
  <div class="section hidden" id="termsSection">
    <h2>Data Privacy Act Consent (RA 10173)</h2>
    <div class="consent-text">
      <p><strong>Republic Act No. 10173 – Data Privacy Act of 2012</strong></p>
      <p>By proceeding, you explicitly grant your consent to the collection, processing, and storage of your personal data (name, email, phone, location) for connecting you with nearby sellers.</p>
    </div>
    <div class="checkbox-group">
      <input type="checkbox" id="termsCheck">
      <label for="termsCheck">I have read and agree.</label>
    </div>
    <div id="termsLocationDisplay" class="coords-display" style="display:none">
      <p>📍 Your location: <span id="termsLat">-</span>, <span id="termsLng">-</span></p>
    </div>
    <div class="row">
      <button class="btn secondary" id="backToFormBtn">← Back</button>
      <button class="btn success" id="acceptTermsBtn">Accept & Find Sellers →</button>
    </div>
  </div>

  <!-- Step 3: Map and seller list with enhanced search -->
  <div class="section hidden" id="sellersMapSection">
    <h2>Nearby Sellers (within <span id="radiusKmDisplay">20</span> km)</h2>
    <div id="sellersCount" class="status" style="display:none"></div>
    <div class="row">
      <input type="search" id="searchInput" placeholder="🔍 Search by breed, product, or location (e.g., leghorn)" style="flex:2;">
      <input type="number" id="radiusInput" value="20" min="1" max="200" style="width:100px;">
      <button class="btn" id="applyFilterBtn">Filter</button>
      <button class="btn secondary" id="refreshSellersBtn">Refresh List</button>
      <button class="btn secondary" id="editInfoBtn">Edit My Info</button>
    </div>
    <div class="map-container" id="mapContainer"><div id="mapView" style="height:100%;"></div></div>
    <div id="sellersGrid" class="seller-grid"></div>
    <div id="inquiriesSection" style="margin-top:20px;">
      <h3>Your Inquiries</h3>
      <div id="inquiriesContainer"></div>
      <button class="btn secondary" id="refreshInquiriesBtn" style="margin-top:8px;">Refresh Inquiries</button>
    </div>
    <button class="btn secondary" id="backToTermsBtn">← Back</button>
  </div>

  <!-- Step 4: Seller detail + message -->
  <div class="section hidden" id="sellerDetailSection">
    <h2 id="sellerTitle">Seller Details</h2>
    <div id="sellerInfo" style="background:#f0f2f6;padding:16px;border-radius:12px;margin:16px 0"></div>
    <h3>Available Listings</h3>
    <div id="sellerListings"></div>
    <h3>Send a Message</h3>
    <form id="messageForm">
      <textarea id="messageContent" rows="4" required placeholder="Hello, I'm interested in your products..."></textarea>
      <div class="checkbox-group">
        <input type="checkbox" id="includeInfoCheck" checked>
        <label for="includeInfoCheck">Include my contact info (name, email, phone) in the message</label>
      </div>
      <div class="row">
        <button type="button" class="btn secondary" id="backToSellersBtn">← Back</button>
        <button type="submit" class="btn success">Send Message</button>
      </div>
    </form>
  </div>
</div>
<div id="modalBackdrop"></div>

<script>
const LOGGED_USER_ID = <?php echo json_encode($logged_user_id); ?>;
</script>
<script src="buy.js"></script>
</body>
</html>