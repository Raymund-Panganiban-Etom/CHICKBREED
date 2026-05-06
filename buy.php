<?php
// Force login check using the same session from your login system
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header('Location: login.php'); // change to your actual login page
    exit;
}
// Get the logged-in user ID from credentialss table
$logged_user_id = (int)$_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Find Sellers Near You – Unified Marketplace</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
  *{box-sizing:border-box}
  body{font-family:Inter,Segoe UI,Arial; background:#f6f8fb; margin:0; padding:20px; display:flex;justify-content:center;}
  .container{width:100%;max-width:1000px;}
  h1{color:#222;text-align:center;}
  .section{background:#fff;padding:20px;margin:16px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);}
  .hidden{display:none;}
  .btn{background:#0b78d1;color:#fff;padding:12px 20px;border-radius:8px;border:0;cursor:pointer;font-weight:600;transition:0.2s;}
  .btn:hover{background:#0960a3;}
  .btn.secondary{background:#e9eef8;color:#0b78d1;border:1px solid #cbd5e1;}
  .btn.success{background:#51cf66;}
  .btn.success:hover{background:#40c057;}
  .row{display:flex;gap:12px;margin-top:12px;flex-wrap:wrap;}
  label{display:block;font-weight:600;margin-top:12px;color:#222;}
  input,textarea,select{width:100%;padding:10px;border:1px solid #e2e6ef;border-radius:8px;font-size:14px;}
  .status{padding:12px;border-radius:8px;margin:12px 0;}
  .status.loading{background:#e7f5ff;color:#0c63e4;}
  .status.success{background:#d3f9d8;color:#2f7c31;}
  .status.error{background:#ffe0e0;color:#d32f2f;}
  .coords-display{background:#f0f2f6;padding:12px;border-radius:8px;margin:12px 0;font-size:13px;}
  .map-container{height:450px;border-radius:12px;margin:16px 0;overflow:hidden;}
  .seller-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-top:16px;}
  .seller-card{background:#fff;border:1px solid #e2e6ef;border-radius:12px;padding:16px;cursor:pointer;transition:0.2s;}
  .seller-card:hover{box-shadow:0 6px 14px rgba(0,0,0,0.1);}
  .distance{color:#0b78d1;font-weight:bold;}
  .step-indicator{display:flex;gap:8px;justify-content:center;margin-bottom:20px;flex-wrap:wrap;}
  .step{padding:8px 16px;border-radius:40px;background:#e9eef8;color:#0b78d1;}
  .step.active{background:#0b78d1;color:#fff;}
  .step.completed{background:#51cf66;color:#fff;}
  .consent-text{max-height:300px;overflow-y:auto;background:#f9fafb;padding:16px;border-radius:12px;font-size:14px;line-height:1.5;}
  .checkbox-group{display:flex;align-items:center;gap:8px;margin-top:16px;}
  @media (max-width:640px){.row{flex-direction:column;}}
</style>
</head>
<body>
<div class="container">
  <h1>🐔 Find Local Sellers Near You</h1>

  <div class="step-indicator">
    <div class="step active" id="step1">1. Your Info</div>
    <div class="step" id="step2">2. Consent</div>
    <div class="step" id="step3">3. Find Sellers</div>
    <div class="step" id="step4">4. Contact</div>
  </div>

  <!-- Step 1: Buyer info + location -->
  <div class="section" id="buyerFormSection">
    <h2>Your Information (RA 10173 compliant)</h2>
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
  </div>

  <!-- Step 2: Terms and Consent (RA 10173 compliant) -->
  <div class="section hidden" id="termsSection">
    <h2>Data Privacy Act Consent (RA 10173)</h2>
    <div class="consent-text">
      <p><strong>Republic Act No. 10173 – Data Privacy Act of 2012</strong></p>
      <p>By proceeding, you explicitly grant your consent to the collection, processing, and storage of the personal data you provided (including full name, email, phone number, and precise GPS location) for the sole purpose of connecting you with nearby sellers of chicken/poultry products.</p>
      <p><strong>Your rights:</strong> Right to access, correct, or delete your data; right to withdraw consent; right to object to processing. You may exercise these rights by contacting us.</p>
      <p><strong>Security:</strong> Your data is stored in a secure database and will never be sold or shared with third parties without your explicit consent.</p>
      <p><strong>Location is required</strong> – we will capture your device's GPS coordinates to find sellers within 20km of your location.</p>
    </div>
    <div class="checkbox-group">
      <input type="checkbox" id="termsCheck">
      <label for="termsCheck">I have read and agree to the collection and processing of my personal data as described.</label>
    </div>
    <div id="termsLocationDisplay" class="coords-display" style="display:none">
      <p>📍 Your location: <span id="termsLat">-</span>, <span id="termsLng">-</span></p>
    </div>
    <div class="row">
      <button class="btn secondary" id="backToFormBtn">← Back</button>
      <button class="btn success" id="acceptTermsBtn">Accept & Find Sellers →</button>
    </div>
  </div>

  <!-- Step 3: Map and seller list -->
  <div class="section hidden" id="sellersMapSection">
    <h2>Nearby Sellers (within 20km)</h2>
    <div id="sellersCount" class="status" style="display:none"></div>
    <div class="map-container" id="mapContainer"><div id="mapView" style="height:100%;"></div></div>
    <div id="sellersGrid" class="seller-grid"></div>
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

<script>
  // Pass the logged-in user ID from PHP to JavaScript
  const LOGGED_USER_ID = <?php echo json_encode($logged_user_id); ?>;

  // ------------------- GLOBALS -------------------
  let currentBuyer = { user_id: LOGGED_USER_ID, buyer_id: null };
  let currentCoords = null;
  let currentSeller = null;
  let map = null;

  // Helper: safe JSON request with error handling
  async function apiRequest(formData) {
    try {
      const response = await fetch('buy_handler.php', { method: 'POST', body: formData });
      const text = await response.text();
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error('Invalid JSON from server:', text);
        throw new Error('Server returned invalid response. Check console for details.');
      }
    } catch (err) {
      console.error('API request failed:', err);
      throw err;
    }
  }

  function showStatus(elId, msg, type) {
    const el = document.getElementById(elId);
    if (el) { el.textContent = msg; el.className = `status ${type}`; el.style.display = 'block'; }
  }

  function switchSection(hideId, showId, stepIndex) {
    document.getElementById(hideId).classList.add('hidden');
    document.getElementById(showId).classList.remove('hidden');
    for (let i = 1; i <= 4; i++) {
      const step = document.getElementById(`step${i}`);
      step.classList.remove('active');
      if (i < stepIndex) step.classList.add('completed');
      else if (i === stepIndex) step.classList.add('active');
      else step.classList.remove('completed');
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // ------------------- STEP 1: Capture location -------------------
  document.getElementById('captureLocBtn').onclick = () => {
    if (!navigator.geolocation) return showStatus('locationStatus', 'Geolocation not supported', 'error');
    showStatus('locationStatus', 'Requesting GPS location...', 'loading');
    navigator.geolocation.getCurrentPosition(
      pos => {
        currentCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
        document.getElementById('capturedLat').textContent = currentCoords.lat.toFixed(6);
        document.getElementById('capturedLng').textContent = currentCoords.lng.toFixed(6);
        document.getElementById('coordsDisplay').style.display = 'block';
        showStatus('locationStatus', `✓ Location captured (accuracy ${pos.coords.accuracy.toFixed(0)}m)`, 'success');
      },
      err => showStatus('locationStatus', 'Permission denied – you can continue with manual address', 'error'),
      { enableHighAccuracy: true, timeout: 10000 }
    );
  };

  document.getElementById('buyerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fullname = document.getElementById('fullname').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const preferences = document.getElementById('preferences').value.trim();
    const address = document.getElementById('location').value.trim();

    if (!fullname || !email || !phone || !preferences) return alert('All fields are required');
    if (!currentCoords && !address) return alert('Please capture your location or provide a manual address');

    currentBuyer = {
      ...currentBuyer,
      fullname, email, phone, preferences,
      location_address: address || `${currentCoords.lat}, ${currentCoords.lng}`,
      latitude: currentCoords?.lat || null,
      longitude: currentCoords?.lng || null
    };
    sessionStorage.setItem('tempBuyer', JSON.stringify(currentBuyer));
    document.getElementById('termsLat').textContent = currentCoords?.lat?.toFixed(6) || 'N/A';
    document.getElementById('termsLng').textContent = currentCoords?.lng?.toFixed(6) || 'N/A';
    document.getElementById('termsLocationDisplay').style.display = currentCoords ? 'block' : 'none';
    switchSection('buyerFormSection', 'termsSection', 2);
  });

  // ------------------- STEP 2: Accept terms (user_id already set) -------------------
  document.getElementById('backToFormBtn').onclick = () => switchSection('termsSection', 'buyerFormSection', 1);

  document.getElementById('acceptTermsBtn').onclick = async () => {
    if (!document.getElementById('termsCheck').checked) return alert('You must accept the consent to continue.');

    const stored = sessionStorage.getItem('tempBuyer');
    if (stored) Object.assign(currentBuyer, JSON.parse(stored));
    if (!currentBuyer.user_id) {
      alert('User ID missing. Please refresh and login again.');
      return;
    }

    const fd = new FormData();
    fd.append('action', 'saveBuyer');
    // user_id is NOT sent to backend – backend will use $_SESSION['user_id']
    fd.append('fullname', currentBuyer.fullname);
    fd.append('email', currentBuyer.email);
    fd.append('phone', currentBuyer.phone);
    fd.append('location_address', currentBuyer.location_address);
    fd.append('latitude', currentBuyer.latitude ?? '');
    fd.append('longitude', currentBuyer.longitude ?? '');
    fd.append('preferences', currentBuyer.preferences);
    fd.append('consent_text', 'Accepted: RA 10173 consent as displayed');
    fd.append('buyer_agent', navigator.userAgent);

    const button = this;
    button.disabled = true;
    button.textContent = 'Saving...';
    try {
      const data = await apiRequest(fd);
      if (data.success) {
        currentBuyer.buyer_id = data.buyer_id;
        await loadNearbySellers();
        switchSection('termsSection', 'sellersMapSection', 3);
      } else {
        alert('Error: ' + (data.error || 'Unknown server error'));
      }
    } catch (err) {
      alert('Network error: ' + err.message);
    } finally {
      button.disabled = false;
      button.textContent = 'Accept & Find Sellers →';
    }
  };

  // ------------------- STEP 3: Find & display sellers -------------------
  async function loadNearbySellers() {
    if (!currentBuyer.latitude || !currentBuyer.longitude) {
      document.getElementById('sellersCount').innerHTML = '<div class="status error">Location missing – restart from step 1</div>';
      return;
    }
    document.getElementById('sellersCount').innerHTML = '<div class="status loading">🔍 Searching for sellers...</div>';
    const fd = new FormData();
    fd.append('action', 'getNearSellers');
    fd.append('buyer_id', currentBuyer.buyer_id);
    fd.append('latitude', currentBuyer.latitude);
    fd.append('longitude', currentBuyer.longitude);
    fd.append('radius', 20);
    try {
      const data = await apiRequest(fd);
      if (data.success) {
        document.getElementById('sellersCount').innerHTML = `<div class="status success">✅ Found ${data.count} seller(s) near you</div>`;
        renderMap(data.data);
        renderSellerCards(data.data);
      } else {
        document.getElementById('sellersCount').innerHTML = `<div class="status error">❌ ${data.error}</div>`;
      }
    } catch (err) {
      document.getElementById('sellersCount').innerHTML = `<div class="status error">Error: ${err.message}</div>`;
    }
  }

  function renderMap(sellers) {
    if (map) map.remove();
    map = L.map('mapView').setView([currentBuyer.latitude, currentBuyer.longitude], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OSM' }).addTo(map);
    L.marker([currentBuyer.latitude, currentBuyer.longitude]).addTo(map).bindPopup('📌 You are here');
    sellers.forEach(s => {
      L.circleMarker([s.latitude, s.longitude], { radius: 8, fillColor: '#ff7800', color: '#fff', weight: 2, fillOpacity: 0.8 })
        .addTo(map)
        .bindPopup(`<b>${escapeHtml(s.description)}</b><br>📍 ${s.distance.toFixed(1)} km<br><a href="#" onclick="viewSellerDetail(${s.user_id})">View</a>`);
    });
  }

  function renderSellerCards(sellers) {
    const grid = document.getElementById('sellersGrid');
    grid.innerHTML = '';
    sellers.forEach(s => {
      const card = document.createElement('div');
      card.className = 'seller-card';
      card.innerHTML = `
        <h3>${escapeHtml(s.description)}</h3>
        <div class="distance">🚗 ${s.distance.toFixed(1)} km away</div>
        <p>📍 ${escapeHtml(s.location_address || 'No address')}</p>
        <div class="row">
          <button class="btn secondary" onclick="viewSellerDetail(${s.user_id})">Details</button>
          <button class="btn success" onclick="viewSellerDetailAndContact(${s.user_id})">Contact</button>
        </div>
      `;
      grid.appendChild(card);
    });
  }

  window.viewSellerDetail = async (sellerId) => {
    const fd = new FormData();
    fd.append('action', 'getSellerInfo');
    fd.append('seller_id', sellerId);
    fd.append('buyer_id', currentBuyer.buyer_id);
    try {
      const data = await apiRequest(fd);
      if (data.success) {
        currentSeller = data.seller;
        currentSeller.listings = data.listings;
        displaySellerDetail();
        switchSection('sellersMapSection', 'sellerDetailSection', 4);
      } else {
        alert('Could not load seller details: ' + data.error);
      }
    } catch (err) {
      alert('Error: ' + err.message);
    }
  };
  window.viewSellerDetailAndContact = (sellerId) => viewSellerDetail(sellerId);

  function displaySellerDetail() {
    document.getElementById('sellerTitle').innerHTML = `Seller: ${escapeHtml(currentSeller.User || 'Seller')}`;
    document.getElementById('sellerInfo').innerHTML = `<p><strong>Name:</strong> ${escapeHtml(currentSeller.User || 'Unnamed')}</p><p><strong>Active listings:</strong> ${currentSeller.listings.length}</p>`;
    document.getElementById('sellerListings').innerHTML = currentSeller.listings.map(l => `
      <div style="background:#f8f9fa;padding:12px;border-radius:12px;margin:8px 0">
        <strong>${escapeHtml(l.description)}</strong><br>
        📞 ${escapeHtml(l.number || 'N/A')}<br>
        💬 ${escapeHtml(l.socmed || 'N/A')}<br>
        📅 ${new Date(l.saved_at).toLocaleDateString()}
      </div>
    `).join('');
  }

  // ------------------- STEP 4: Send message -------------------
  document.getElementById('messageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = document.getElementById('messageContent').value.trim();
    if (!message) return alert('Please type a message');
    const includeInfo = document.getElementById('includeInfoCheck').checked;
    let buyerInfo = '';
    if (includeInfo) buyerInfo = `Name: ${currentBuyer.fullname}\nEmail: ${currentBuyer.email}\nPhone: ${currentBuyer.phone}`;

    const fd = new FormData();
    fd.append('action', 'sendMessage');
    fd.append('buyer_id', currentBuyer.buyer_id);
    fd.append('seller_id', currentSeller.user_id);
    fd.append('location_id', currentSeller.listings[0]?.location_id || 0);
    fd.append('message', message);
    fd.append('buyer_info', buyerInfo);
    try {
      const data = await apiRequest(fd);
      if (data.success) {
        alert('✅ Message sent! The seller will contact you soon.');
        document.getElementById('messageContent').value = '';
      } else {
        alert('Failed to send: ' + (data.error || 'Unknown'));
      }
    } catch (err) {
      alert('Error: ' + err.message);
    }
  });

  document.getElementById('backToSellersBtn').onclick = () => switchSection('sellerDetailSection', 'sellersMapSection', 3);
  document.getElementById('backToTermsBtn').onclick = () => switchSection('sellersMapSection', 'termsSection', 2);

  function escapeHtml(str) { return String(str || '').replace(/[&<>]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c])); }
</script>
</body>
</html>