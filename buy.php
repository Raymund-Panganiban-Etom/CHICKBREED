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
<!-- MarkerCluster CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
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
  .item{background:#fff;padding:12px;border-radius:10px;display:flex;gap:12px;align-items:flex-start;box-shadow:0 6px 18px rgba(2,6,23,.04)}
  .thumb{width:96px;height:72px;border-radius:6px;background:#f0f2f6;object-fit:cover;border:1px solid #eee}
  .meta{flex:1}
  .meta h4{margin:0 0 6px 0;font-size:16px}
  .meta p{margin:0;color:#666;font-size:13px}
  .actions{display:flex;flex-direction:column;gap:6px}
  .remove{background:#ff6b6b;color:#fff;border:0;padding:6px 8px;border-radius:6px;cursor:pointer}
  .distance{color:#0b78d1;font-weight:bold;}
  .step-indicator{display:flex;gap:8px;justify-content:center;margin-bottom:20px;flex-wrap:wrap;}
  .step{padding:8px 16px;border-radius:40px;background:#e9eef8;color:#0b78d1;}
  .step.active{background:#0b78d1;color:#fff;}
  .step.completed{background:#51cf66;color:#fff;}
  #modalBackdrop{position:fixed;inset:0;background:rgba(0,0,0,0.45);display:none;z-index:50}
  .modal-active{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);width:720px;max-width:96%;z-index:60;box-shadow:0 10px 30px rgba(0,0,0,.3)}
  .consent-text{max-height:300px;overflow-y:auto;background:#f9fafb;padding:16px;border-radius:12px;font-size:14px;line-height:1.5;}
  .checkbox-group{display:flex;align-items:center;gap:8px;margin-top:16px;}
  @media (max-width:640px){.row{flex-direction:column;}}
</style>
</head>
<body>
<div class="container">
  <h1>🐔 Find Local Sellers Near You</h1>
  <div id="dashboardHeader" style="display:flex;justify-content:space-between;gap:12px;align-items:start;margin-bottom:12px">
    <div id="profileBox" style="flex:0 0 320px;"> 
      <div style="background:#fff;padding:12px;border-radius:8px;border:1px solid #e9eef8">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div><strong>Your Saved Profile</strong><div id="currentProfileDisplay" style="margin-top:8px;color:#333;font-size:14px">No profile saved.</div></div>
          <div><button class="btn secondary" id="dashboardAddBtn">Add</button></div>
        </div>
      </div>
    </div>
    <div id="searchBox" style="flex:1;display:flex;justify-content:flex-end;align-items:center">
      <div style="background:#fff;padding:12px;border-radius:8px;border:1px solid #e9eef8;display:flex;gap:8px;align-items:center">
        <input type="search" id="headerSearchInput" placeholder="Search sellers or listings" style="padding:8px;border:1px solid #e2e6ef;border-radius:6px;width:420px">
        <input type="number" id="headerRadiusInput" value="20" min="1" max="200" style="width:80px;padding:8px;border:1px solid #e2e6ef;border-radius:6px">
        <button class="btn" id="headerSearchBtn">Search</button>
      </div>
    </div>
  </div>
  <div id="modalBackdrop"></div>
  <div class="step-indicator">
    <div class="step active" id="step1">1. Your Info</div>
    <div class="step" id="step2">2. Consent</div>
    <div class="step" id="step3">3. Find Sellers</div>
    <div class="step" id="step4">4. Contact</div>
  </div>

  <!-- Step 1 -->
  <div class="section" id="buyerFormSection">
    <div style="display:flex;justify-content:space-between;align-items:start">
      <h2>Your Information (RA 10173 compliant)</h2>
      <button id="buyerFormCloseBtn" class="btn secondary" style="height:36px;align-self:flex-start;">Close</button>
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
    <div class="row" style="margin-top:12px;align-items:center">
      <input type="search" id="searchInput" placeholder="Search by product, address, or username" style="flex:1;max-width:60%" />
      <label style="width:120px">Radius (km)</label>
      <input type="number" id="radiusInput" value="20" min="1" max="200" style="width:80px" />
      <button class="btn secondary" id="editInfoBtn">Edit My Info</button>
      <button class="btn" id="refreshSellersBtn">Search</button>
    </div>
    <div class="map-container" id="mapContainer"><div id="mapView" style="height:100%;"></div></div>
    <div id="sellersGrid" class="seller-grid"></div>
    <div id="inquiriesSection" style="margin-top:20px;">
      <h3>Your Inquiries</h3>
      <div id="inquiriesList" style="display:block;gap:12px;">
        <div class="status" id="inquiriesStatus" style="display:none"></div>
        <div id="inquiriesContainer"></div>
      </div>
      <div class="row" style="margin-top:8px">
        <button class="btn secondary" id="refreshInquiriesBtn">Refresh Inquiries</button>
      </div>
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

<script>
  const LOGGED_USER_ID = <?php echo json_encode($logged_user_id); ?>;
  let currentBuyer = { user_id: LOGGED_USER_ID, buyer_id: null };
  let currentProfile = null;
  let currentCoords = null;
  let currentSeller = null;
  let map = null;
  let sellerMarkers = [];
  let allSellers = [];
  let selectedListingId = 0;
  let inquiryPollInterval = null;
  let modalOpen = false;

  async function apiRequest(formData) {
    try {
      const response = await fetch('buy_handler.php', { method: 'POST', body: formData });
      const text = await response.text();
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error('Invalid JSON from server — response text:', text);
        throw new Error('Server returned invalid response. See console for full response.');
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
    if (modalOpen) {
      const modalIds = ['buyerFormSection','termsSection'];
      modalIds.forEach(id => document.getElementById(id).classList.remove('modal-active'));
      if (modalIds.includes(showId)) {
        document.getElementById(showId).classList.add('modal-active');
        showBackdrop();
      } else {
        hideBackdrop();
        modalOpen = false;
      }
    }
    for (let i = 1; i <= 4; i++) {
      const step = document.getElementById(`step${i}`);
      step.classList.remove('active');
      if (i < stepIndex) step.classList.add('completed');
      else if (i === stepIndex) step.classList.add('active');
      else step.classList.remove('completed');
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
    // Only invalidate map if map exists and we are showing the map section
    if (showId === 'sellersMapSection' && map) {
      setTimeout(() => map.invalidateSize(), 100);
    }
  }

  function showBackdrop(){ const b = document.getElementById('modalBackdrop'); if (b) b.style.display = 'block'; }
  function hideBackdrop(){ const b = document.getElementById('modalBackdrop'); if (b) b.style.display = 'none'; ['buyerFormSection','termsSection'].forEach(id => { const el = document.getElementById(id); if (el) el.classList.remove('modal-active'); }); }

  // Capture location
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

  document.getElementById('backToFormBtn').onclick = () => switchSection('termsSection', 'buyerFormSection', 1);

  document.getElementById('acceptTermsBtn').addEventListener('click', async function() {
    if (!document.getElementById('termsCheck').checked) return alert('You must accept the consent to continue.');
    const stored = sessionStorage.getItem('tempBuyer');
    if (stored) Object.assign(currentBuyer, JSON.parse(stored));
    if (!currentBuyer.user_id) { alert('User ID missing. Please refresh and login again.'); return; }

    const fd = new FormData();
    fd.append('action', 'saveBuyer');
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
        try {
          const pfd = new FormData();
          pfd.append('action','saveBuyerProfile');
          pfd.append('fullname', currentBuyer.fullname);
          pfd.append('email', currentBuyer.email);
          pfd.append('phone', currentBuyer.phone);
          pfd.append('preferences', currentBuyer.preferences || '');
          pfd.append('location_address', currentBuyer.location_address || '');
          pfd.append('latitude', currentBuyer.latitude ?? '');
          pfd.append('longitude', currentBuyer.longitude ?? '');
          const pres = await apiRequest(pfd);
          if (pres.success) {
            const gfd = new FormData(); gfd.append('action','getBuyerProfile'); gfd.append('profile_id', pres.profile_id);
            const gres = await apiRequest(gfd);
            if (gres.success) { currentProfile = gres.profile; renderCurrentProfileDisplay(); }
          }
        } catch (e) { console.warn('Could not save profile:', e); }
        await loadNearbySellers();
        try { await loadProfiles(); } catch(e){}
        switchSection('termsSection', 'sellersMapSection', 3);
        if (modalOpen) { modalOpen = false; hideBackdrop(); }
      } else {
        alert('Error: ' + (data.error || 'Unknown server error'));
      }
    } catch (err) { alert('Network error: ' + err.message); }
    finally { button.disabled = false; button.textContent = 'Accept & Find Sellers →'; }
  });

  async function loadNearbySellers() {
    if (!currentBuyer.latitude || !currentBuyer.longitude) {
      document.getElementById('sellersCount').innerHTML = '<div class="status error">Location missing – restart from step 1</div>';
      return;
    }
    document.getElementById('sellersCount').innerHTML = '<div class="status loading">🔍 Searching for sellers...</div>';
    const radius = parseFloat(document.getElementById('radiusInput').value || 20);
    const fd = new FormData();
    fd.append('action', 'getNearSellers');
    fd.append('buyer_id', currentBuyer.buyer_id);
    fd.append('latitude', currentBuyer.latitude);
    fd.append('longitude', currentBuyer.longitude);
    fd.append('radius', radius);
    try {
      const data = await apiRequest(fd);
      if (data.success) {
        document.getElementById('sellersCount').innerHTML = `<div class="status success">✅ Found ${data.count} seller(s) near you</div>`;
        allSellers = data.data || [];
        renderMap(allSellers);
        renderSellerCards(allSellers);
      } else {
        document.getElementById('sellersCount').innerHTML = `<div class="status error">❌ ${data.error}</div>`;
      }
    } catch (err) {
      document.getElementById('sellersCount').innerHTML = `<div class="status error">Error: ${err.message}</div>`;
    }
  }

  function renderMap(sellers) {
    // FIX: Guard against missing or invalid buyer coordinates
    if (!currentBuyer.latitude || !currentBuyer.longitude || isNaN(currentBuyer.latitude) || isNaN(currentBuyer.longitude)) {
      console.warn('Cannot render map: buyer coordinates missing or invalid');
      return;
    }
    if (map) map.remove();
    map = L.map('mapView').setView([currentBuyer.latitude, currentBuyer.longitude], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OSM' }).addTo(map);
    L.marker([currentBuyer.latitude, currentBuyer.longitude]).addTo(map).bindPopup('📌 You are here');
    sellerMarkers.forEach(m => { try { map.removeLayer(m); } catch(e){} });
    sellerMarkers = [];
    const markersGroup = L.markerClusterGroup();
    const seen = {};
    sellers.forEach((s, idx) => {
      let lat = parseFloat(s.latitude);
      let lng = parseFloat(s.longitude);
      const key = lat.toFixed(6) + ',' + lng.toFixed(6);
      seen[key] = (seen[key] || 0) + 1;
      const seq = seen[key] - 1;
      if (seq > 0) {
        const offset = 0.00006 * seq;
        lat = lat + offset;
        lng = lng + (offset / Math.cos(lat * Math.PI / 180));
      }
      const marker = L.marker([lat, lng]);
      marker.bindPopup(`<b>${escapeHtml(s.description)}</b><br>📍 ${s.distance.toFixed(1)} km<br><a href="#" onclick="viewSellerDetail(${s.user_id})">View</a><br><small>Coordinates: ${parseFloat(s.latitude).toFixed(6)}, ${parseFloat(s.longitude).toFixed(6)}</small>`);
      markersGroup.addLayer(marker);
      sellerMarkers.push(marker);
    });
    map.addLayer(markersGroup);
    // Force a size recalculation now that the map is visible
    setTimeout(() => map.invalidateSize(), 100);
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

  function applyFilters() {
    // FIX: Do not attempt to apply filters if buyer coordinates are missing
    if (!currentBuyer.latitude || !currentBuyer.longitude) {
      console.warn('Cannot apply filters: buyer coordinates missing');
      return;
    }
    const q = (document.getElementById('searchInput').value || '').toLowerCase().trim();
    const radius = parseFloat(document.getElementById('radiusInput').value || 20);
    const filtered = allSellers.filter(s => {
      if (s.distance > radius) return false;
      if (!q) return true;
      const pack = [s.description, s.location_address, s.seller_name, s.socmed].join(' ').toLowerCase();
      return pack.indexOf(q) !== -1;
    });
    renderMap(filtered);
    renderSellerCards(filtered);
    document.getElementById('sellersCount').innerHTML = `<div class="status success">✅ Showing ${filtered.length} seller(s)</div>`;
  }

  async function loadBuyerProfile() {
    try {
      const fd = new FormData(); fd.append('action','getBuyer');
      const data = await apiRequest(fd);
      if (data.success) {
        const b = data.buyer;
        currentBuyer.buyer_id = b.buyer_id || currentBuyer.buyer_id;
        currentBuyer.user_id = b.user_id || currentBuyer.user_id;
        document.getElementById('fullname').value = b.fullname || '';
        document.getElementById('email').value = b.email || '';
        document.getElementById('phone').value = b.phone || '';
        document.getElementById('preferences').value = b.preferences || '';
        document.getElementById('location').value = b.location_address || '';
        if (b.latitude && b.longitude) {
          currentCoords = { lat: parseFloat(b.latitude), lng: parseFloat(b.longitude) };
          document.getElementById('capturedLat').textContent = currentCoords.lat.toFixed(6);
          document.getElementById('capturedLng').textContent = currentCoords.lng.toFixed(6);
          document.getElementById('coordsDisplay').style.display = 'block';
        }
        switchSection('sellersMapSection','buyerFormSection',1);
      } else { alert('Could not load profile: ' + (data.error||'')); }
    } catch (e) { alert('Error loading profile: ' + e.message); }
  }

  document.getElementById('refreshSellersBtn').addEventListener('click', () => loadNearbySellers());
  document.getElementById('searchInput').addEventListener('input', () => applyFilters());
  document.getElementById('radiusInput').addEventListener('change', () => applyFilters());
  document.getElementById('editInfoBtn').addEventListener('click', () => loadBuyerProfile());

  window.viewSellerDetail = async (sellerId) => {
    const fd = new FormData();
    fd.append('action', 'getSellerInfo');
    fd.append('seller_id', sellerId);
    fd.append('buyer_id', currentBuyer.buyer_id);
    try {
      const data = await apiRequest(fd);
      if (data.success) {
        currentSeller = data.seller || {};
        currentSeller.user_id = currentSeller.user_id || currentSeller.ids || sellerId;
        currentSeller.listings = data.listings || [];
        displaySellerDetail();
        switchSection('sellersMapSection', 'sellerDetailSection', 4);
      } else { alert('Could not load seller details: ' + data.error); }
    } catch (err) { alert('Error: ' + err.message); }
  };
  window.viewSellerDetailAndContact = (sellerId) => viewSellerDetail(sellerId);

  function displaySellerDetail() {
    document.getElementById('sellerTitle').innerHTML = `Seller: ${escapeHtml(currentSeller.User || currentSeller.seller_name || 'Seller')}`;
    document.getElementById('sellerInfo').innerHTML = `<p><strong>Name:</strong> ${escapeHtml(currentSeller.User || currentSeller.seller_name || 'Unnamed')}</p><p><strong>Active listings:</strong> ${currentSeller.listings.length}</p>`;
    document.getElementById('sellerListings').innerHTML = currentSeller.listings.map(l => `
      <div style="background:#f8f9fa;padding:12px;border-radius:12px;margin:8px 0;display:flex;gap:12px;align-items:flex-start">
        <div style="flex:0 0 28px"><input type="radio" name="selectedListing" value="${l.location_id}" ${l === currentSeller.listings[0] ? 'checked' : ''} onchange="window.selectListing(${l.location_id})"></div>
        <div style="flex:1"><strong>${escapeHtml(l.description)}</strong><br>📞 ${escapeHtml(l.number || 'N/A')}<br>💬 ${escapeHtml(l.socmed || 'N/A')}<br>📅 ${new Date(l.saved_at).toLocaleDateString()}</div>
      </div>
    `).join('');
    selectedListingId = currentSeller.listings[0]?.location_id || 0;
  }
  window.selectListing = (locId) => { selectedListingId = locId; }

  document.getElementById('messageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = document.getElementById('messageContent').value.trim();
    if (!message) return alert('Please type a message');

    if (!currentProfile || !currentProfile.profile_id) {
      alert('Please save a profile first (Accept & Find Sellers) or select a saved profile before sending messages.');
      return;
    }
    if (!currentBuyer || !currentBuyer.buyer_id) {
      alert('Please complete the consent/save step first (Accept & Find Sellers).');
      return;
    }

    if (!currentSeller || !currentSeller.user_id) {
      if (Array.isArray(allSellers) && allSellers.length > 0) {
        try {
          const firstId = allSellers[0].user_id || allSellers[0].ids;
          const fdInfo = new FormData(); fdInfo.append('action','getSellerInfo'); fdInfo.append('seller_id', firstId);
          const info = await apiRequest(fdInfo);
          if (info && info.success) {
            currentSeller = info.seller || {};
            currentSeller.user_id = currentSeller.user_id || currentSeller.ids || firstId;
            currentSeller.listings = info.listings || [];
          } else { return alert('No seller selected. Please choose a seller to contact.'); }
        } catch (e) { return alert('Could not load seller details. Please select a seller manually.'); }
      } else { return alert('No seller selected. Please choose a seller to contact.'); }
    }

    const includeInfo = document.getElementById('includeInfoCheck').checked;
    let buyerInfo = '';
    if (includeInfo) buyerInfo = `Name: ${currentBuyer.fullname}\nEmail: ${currentBuyer.email}\nPhone: ${currentBuyer.phone}`;

    const fd = new FormData();
    fd.append('action', 'sendMessage');
    fd.append('buyer_id', currentBuyer.buyer_id);
    fd.append('profile_id', currentProfile.profile_id || '');
    fd.append('seller_id', currentSeller.user_id);
    fd.append('location_id', selectedListingId || currentSeller.listings[0]?.location_id || 0);
    fd.append('message', message);
    fd.append('buyer_info', buyerInfo);
    try {
      const data = await apiRequest(fd);
      if (data.success) {
    alert('✅ Message sent! The seller will contact you soon.');
    document.getElementById('messageContent').value = '';
    if (inquiryPollInterval) clearInterval(inquiryPollInterval);
    inquiryPollInterval = setInterval(checkInquiriesOnce, 8000);
    try { await loadBuyerInquiries(); } catch(e) {}
    switchSection('sellerDetailSection','sellersMapSection',3);
    if (modalOpen) { modalOpen = false; hideBackdrop(); }
} else {
        alert('Failed to send: ' + (data.error || 'Unknown'));
      }
    } catch (err) { alert('Error: ' + err.message); }
  });

  document.getElementById('backToSellersBtn').onclick = () => switchSection('sellerDetailSection', 'sellersMapSection', 3);
  document.getElementById('backToTermsBtn').onclick = () => switchSection('sellersMapSection', 'termsSection', 2);

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  (async function prefillBuyerIfExists(){
    try {
      const fd = new FormData(); fd.append('action','getBuyer');
      const data = await apiRequest(fd);
      if (data.success) {
        const b = data.buyer;
        currentBuyer.buyer_id = b.buyer_id || currentBuyer.buyer_id;
        currentBuyer.user_id = b.user_id || currentBuyer.user_id;
        document.getElementById('fullname').value = b.fullname || '';
        document.getElementById('email').value = b.email || '';
        document.getElementById('phone').value = b.phone || '';
        document.getElementById('preferences').value = b.preferences || '';
        document.getElementById('location').value = b.location_address || '';
        if (b.latitude && b.longitude) {
          currentCoords = { lat: parseFloat(b.latitude), lng: parseFloat(b.longitude) };
          document.getElementById('capturedLat').textContent = currentCoords.lat.toFixed(6);
          document.getElementById('capturedLng').textContent = currentCoords.lng.toFixed(6);
          document.getElementById('coordsDisplay').style.display = 'block';
        }
        // FIX: Only go to map if coordinates are present
        try {
          if (currentBuyer.latitude && currentBuyer.longitude) {
            await loadNearbySellers();
            await loadBuyerInquiries();
            switchSection('buyerFormSection', 'sellersMapSection', 3);
          } else if (currentCoords) {
            currentBuyer.latitude = currentCoords.lat;
            currentBuyer.longitude = currentCoords.lng;
            await loadNearbySellers();
            await loadBuyerInquiries();
            switchSection('buyerFormSection', 'sellersMapSection', 3);
          } else {
            // No location: stay on profile form
            console.log('Buyer profile lacks location. Please capture location.');
            switchSection('sellersMapSection', 'buyerFormSection', 1);
          }
        } catch (e) { /* non-fatal */ }
      }
    } catch (e) { /* ignore */ }
    try { await loadProfiles(); } catch(e) { /* ignore */ }
    try { await loadBuyerInquiries(); } catch(e) { /* ignore */ }
  })();

  async function loadProfiles() {
    try {
      const fd = new FormData(); fd.append('action','getBuyerProfiles');
      const data = await apiRequest(fd);
      if (data.success) {
        renderProfilesList(data.data || []);
        renderCurrentProfileDisplay();
      }
    } catch (e) { console.warn('Could not load profiles', e); }
  }

  function renderCurrentProfileDisplay() {
    const el = document.getElementById('currentProfileDisplay');
    if (!currentProfile) { el.innerHTML = 'No profile saved.'; return; }
    el.innerHTML = `<div style="font-size:14px"><strong>${escapeHtml(currentProfile.fullname)}</strong><br>${escapeHtml(currentProfile.email || '')} ${escapeHtml(currentProfile.phone || '')}<br><small>${escapeHtml(currentProfile.location_address || '')}</small></div>`;
  }

  function renderProfilesList(list) {
    const el = document.getElementById('profilesList');
    el.innerHTML = '';
    if (!list || list.length === 0) { el.innerHTML = '<div class="small">No saved profiles. Add one and Accept to save.</div>'; return; }
    list.forEach(p => {
      const item = document.createElement('div'); item.className = 'item';
      const thumb = document.createElement('div'); thumb.className = 'thumb';
      thumb.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#b0b6c3;font-size:12px">Profile</div>';
      const meta = document.createElement('div'); meta.className = 'meta';
      meta.innerHTML = `<h4>${escapeHtml(p.fullname || '(no name)')}</h4><p>${escapeHtml(p.email || '')} ${escapeHtml(p.phone || '')}</p><p style="font-size:12px;color:#888">${escapeHtml(p.location_address || '')}</p>`;
      const actions = document.createElement('div'); actions.className = 'actions';
      const useBtn = document.createElement('button'); useBtn.className='btn secondary'; useBtn.textContent='Use'; useBtn.onclick = () => {
        currentProfile = p;
        document.getElementById('fullname').value = p.fullname || '';
        document.getElementById('email').value = p.email || '';
        document.getElementById('phone').value = p.phone || '';
        document.getElementById('preferences').value = p.preferences || '';
        document.getElementById('location').value = p.location_address || '';
        if (p.latitude && p.longitude) {
          currentCoords = { lat: parseFloat(p.latitude), lng: parseFloat(p.longitude) };
          document.getElementById('capturedLat').textContent = currentCoords.lat.toFixed(6);
          document.getElementById('capturedLng').textContent = currentCoords.lng.toFixed(6);
          document.getElementById('coordsDisplay').style.display = 'block';
        }
        currentBuyer.fullname = p.fullname; currentBuyer.email = p.email; currentBuyer.phone = p.phone;
        renderCurrentProfileDisplay();
      };
      const editBtn = document.createElement('button'); editBtn.className='btn'; editBtn.textContent='Edit'; editBtn.onclick = () => {
        currentProfile = p;
        document.getElementById('fullname').value = p.fullname || '';
        document.getElementById('email').value = p.email || '';
        document.getElementById('phone').value = p.phone || '';
        document.getElementById('preferences').value = p.preferences || '';
        document.getElementById('location').value = p.location_address || '';
        modalOpen = true; switchSection('sellersMapSection','buyerFormSection',1);
      };
      const delBtn = document.createElement('button'); delBtn.className='remove'; delBtn.textContent='Delete'; delBtn.onclick = async () => {
        if (!confirm('Delete this profile?')) return;
        try { const fd = new FormData(); fd.append('action','deleteBuyerProfile'); fd.append('profile_id', p.profile_id); const res = await apiRequest(fd); if (res.success) loadProfiles(); } catch(e){alert('Failed to delete');}
      };
      actions.appendChild(useBtn); actions.appendChild(editBtn); actions.appendChild(delBtn);
      item.appendChild(thumb); item.appendChild(meta); item.appendChild(actions);
      el.appendChild(item);
    });
  }

  document.getElementById('refreshProfilesBtn').addEventListener('click', () => loadProfiles());
  document.getElementById('addProfileBtn').addEventListener('click', () => {
    currentProfile = null;
    document.getElementById('buyerForm').reset();
    document.getElementById('coordsDisplay').style.display = 'none';
    modalOpen = true;
    switchSection('sellersMapSection','buyerFormSection',1);
  });
  document.getElementById('dashboardAddBtn').addEventListener('click', () => {
    currentProfile = null;
    document.getElementById('buyerForm').reset();
    document.getElementById('coordsDisplay').style.display = 'none';
    modalOpen = true;
    switchSection('sellersMapSection','buyerFormSection',1);
    showBackdrop();
  });
  document.getElementById('buyerFormCloseBtn').addEventListener('click', () => {
    modalOpen = false;
    hideBackdrop();
    switchSection('buyerFormSection','sellersMapSection',3);
  });
  document.getElementById('headerSearchBtn').addEventListener('click', async () => {
    const q = (document.getElementById('headerSearchInput').value || '').trim();
    const radius = parseFloat(document.getElementById('headerRadiusInput').value || 20);
    document.getElementById('searchInput').value = q;
    document.getElementById('radiusInput').value = radius;
    await loadNearbySellers();
    applyFilters();
    switchSection('buyerFormSection','sellersMapSection',3);
  });

  async function checkInquiriesOnce() {
    try {
      const fd = new FormData(); fd.append('action','getBuyerInquiries');
      const data = await apiRequest(fd);
      if (data.success) {
        renderInquiriesList(data.data || []);
        for (const iq of data.data || []) {
          if (iq.inquiry_status === 'contacted') {
            alert('✅ Seller accepted your inquiry for: ' + (iq.listing_desc || 'a listing'));
            if (inquiryPollInterval) { clearInterval(inquiryPollInterval); inquiryPollInterval = null; }
            break;
          }
          if (iq.inquiry_status === 'archived') {
            alert('ℹ️ Seller ignored your inquiry for: ' + (iq.listing_desc || 'a listing'));
            if (inquiryPollInterval) { clearInterval(inquiryPollInterval); inquiryPollInterval = null; }
            break;
          }
        }
      }
    } catch (e) { /* ignore */ }
  }

  async function loadBuyerInquiries() {
    document.getElementById('inquiriesStatus').style.display = 'block';
    document.getElementById('inquiriesStatus').textContent = 'Loading inquiries...';
    try {
      const fd = new FormData(); fd.append('action','getBuyerInquiries');
      const data = await apiRequest(fd);
      if (data.success) {
        try { document.getElementById('inquiriesSection').style.display = 'block'; } catch(e){}
        renderInquiriesList(data.data || []);
        document.getElementById('inquiriesStatus').style.display = 'none';
      } else { document.getElementById('inquiriesStatus').textContent = 'Error loading inquiries'; }
    } catch (e) { document.getElementById('inquiriesStatus').textContent = 'Network error'; }
  }

  function renderInquiriesList(items) {
    const container = document.getElementById('inquiriesContainer');
    container.innerHTML = '';
    if (!items || items.length === 0) { container.innerHTML = '<div class="status">You have no inquiries yet.</div>'; return; }
    const active = items.filter(i => { const s = (i.inquiry_status||'').toLowerCase().trim(); return s === '' || s.startsWith('active'); });
    const history = items.filter(i => !active.includes(i));
    if (active.length) {
      const h = document.createElement('div'); h.innerHTML = '<h4>Active Inquiries</h4>'; container.appendChild(h);
      active.forEach(iq => {
        const item = document.createElement('div'); item.className = 'item';
        const thumb = document.createElement('div'); thumb.className = 'thumb'; thumb.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#b0b6c3;font-size:12px">Inquiry</div>';
        const meta = document.createElement('div'); meta.className = 'meta';
        meta.innerHTML = `<h4>${escapeHtml(iq.listing_desc || 'Listing')}</h4><p>Seller: ${escapeHtml(iq.seller_name || iq.seller_id || '')}</p><p style="font-size:12px;color:#888">Profile: ${escapeHtml(iq.profile_name || '')}</p><p style="font-size:12px;color:#888">${new Date(iq.interested_at).toLocaleString()}</p>`;
        const actions = document.createElement('div'); actions.className = 'actions';
        const viewBtn = document.createElement('button'); viewBtn.className='btn'; viewBtn.textContent='View'; viewBtn.onclick = () => { if (iq.seller_id) viewSellerDetail(iq.seller_id); };
        actions.appendChild(viewBtn);
        item.appendChild(thumb); item.appendChild(meta); item.appendChild(actions);
        container.appendChild(item);
      });
    }
    if (history.length) {
      const h2 = document.createElement('div'); h2.innerHTML = '<h4>Inquiry History</h4>'; container.appendChild(h2);
      history.forEach(iq => {
        const item = document.createElement('div'); item.className = 'item';
        const thumb = document.createElement('div'); thumb.className = 'thumb'; thumb.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#b0b6c3;font-size:12px">History</div>';
        const meta = document.createElement('div'); meta.className = 'meta';
        const statusText = escapeHtml(iq.inquiry_status || '');
        meta.innerHTML = `<h4>${escapeHtml(iq.listing_desc || 'Listing')}</h4><p>Seller: ${escapeHtml(iq.seller_name || iq.seller_id || '')}</p><p style="font-size:12px;color:#888">Profile: ${escapeHtml(iq.profile_name || '')}</p><p style="font-size:12px;color:#888">${new Date(iq.interested_at).toLocaleString()}</p><p style="margin-top:6px"><strong>Status:</strong> ${statusText}</p>`;
        const actions = document.createElement('div'); actions.className = 'actions';
        const viewBtn = document.createElement('button'); viewBtn.className='btn secondary'; viewBtn.textContent='View'; viewBtn.onclick = () => { if (iq.seller_id) viewSellerDetail(iq.seller_id); };
        actions.appendChild(viewBtn);
        item.appendChild(thumb); item.appendChild(meta); item.appendChild(actions);
        container.appendChild(item);
      });
    }
  }
  document.getElementById('refreshInquiriesBtn').addEventListener('click', () => loadBuyerInquiries());
</script>
</body>
</html>