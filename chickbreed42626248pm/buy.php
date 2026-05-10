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
<title>Find Sellers Near You – FarmConnect</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<style>
  *{box-sizing:border-box}
  body{font-family:Inter,Segoe UI,Arial; background:#f6f8fb; margin:0; padding:20px; display:flex;justify-content:center;}
  .container{width:100%;max-width:1000px;}
  h1{color:#222;text-align:center; font-size:clamp(1.5rem,5vw,2.5rem);}
  .section{background:#fff;padding:20px;margin:16px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);}
  .hidden{display:none;}
  .btn{background:#0b78d1;color:#fff;padding:12px 20px;border-radius:8px;border:0;cursor:pointer;font-weight:600;transition:0.2s;display:inline-block;text-align:center;min-height:44px;min-width:44px;}
  .btn:hover{background:#0960a3;}
  .btn.secondary{background:#e9eef8;color:#0b78d1;border:1px solid #cbd5e1;}
  .btn.success{background:#51cf66;}
  .btn.success:hover{background:#40c057;}
  .row{display:flex;gap:12px;margin-top:12px;flex-wrap:wrap;}
  label{display:block;font-weight:600;margin-top:12px;color:#222;}
  input,textarea,select{width:100%;padding:10px;border:1px solid #e2e6ef;border-radius:8px;font-size:16px;}
  .status{padding:12px;border-radius:8px;margin:12px 0;}
  .status.loading{background:#e7f5ff;color:#0c63e4;}
  .status.success{background:#d3f9d8;color:#2f7c31;}
  .status.error{background:#ffe0e0;color:#d32f2f;}
  .coords-display{background:#f0f2f6;padding:12px;border-radius:8px;margin:12px 0;font-size:13px;}
  .map-container{height:450px;border-radius:12px;margin:16px 0;overflow:hidden;}
  .seller-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-top:16px;}
  .seller-card{background:#fff;border:1px solid #e2e6ef;border-radius:12px;padding:16px;cursor:pointer;transition:0.2s;}
  .seller-card:hover{box-shadow:0 6px 14px rgba(0,0,0,0.1);}
  .item{background:#fff;padding:12px;border-radius:10px;display:flex;gap:12px;align-items:flex-start;box-shadow:0 6px 18px rgba(2,6,23,.04);flex-wrap:wrap;}
  .thumb{width:96px;height:72px;border-radius:6px;background:#f0f2f6;object-fit:cover;border:1px solid #eee;max-width:100%;}
  .meta{flex:1;min-width:150px;}
  .meta h4{margin:0 0 6px 0;font-size:16px}
  .meta p{margin:0;color:#666;font-size:13px}
  .actions{display:flex;flex-direction:row;gap:6px;flex-wrap:wrap;}
  .remove{background:#ff6b6b;color:#fff;border:0;padding:6px 8px;border-radius:6px;cursor:pointer}
  .distance{color:#0b78d1;font-weight:bold;}
  .step-indicator{display:flex;gap:8px;justify-content:center;margin-bottom:20px;flex-wrap:wrap;}
  .step{padding:8px 16px;border-radius:40px;background:#e9eef8;color:#0b78d1;font-size:clamp(12px,4vw,16px);}
  .step.active{background:#0b78d1;color:#fff;}
  .step.completed{background:#51cf66;color:#fff;}
  #modalBackdrop{position:fixed;inset:0;background:rgba(0,0,0,0.45);display:none;z-index:50}
  .modal-active{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);width:720px;max-width:96%;z-index:60;box-shadow:0 10px 30px rgba(0,0,0,.3)}
  .consent-text{max-height:300px;overflow-y:auto;background:#f9fafb;padding:16px;border-radius:12px;font-size:14px;line-height:1.5;}
  .checkbox-group{display:flex;align-items:center;gap:8px;margin-top:16px;}
  /* Responsive */
  @media (max-width:768px){
    .container{padding:0 12px;}
    .row{flex-direction:column;}
    .map-container{height:300px;}
    .btn{width:100%;}
    .seller-grid{grid-template-columns:1fr;}
    .thumb{width:72px;height:56px;}
    .actions{justify-content:flex-end;}
    .step{padding:6px 12px;}
  }
  @media (min-width:769px) and (max-width:1024px){
    .seller-grid{grid-template-columns:repeat(2,1fr);}
    .map-container{height:380px;}
  }
  button, .btn, .remove{min-height:44px;min-width:44px;}
  img,svg,iframe{max-width:100%;height:auto;}
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

  <!-- Step 1: Buyer info -->
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
      <p>By proceeding, you explicitly grant your consent to the collection, processing, and storage of the personal data you provided (including full name, email, phone number, and precise GPS location) for the sole purpose of connecting you with nearby sellers of chicken/poultry products.</p>
      <p><strong>Your rights:</strong> Right to access, correct, or delete your data; right to withdraw consent; right to object to processing.</p>
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

  <!-- Step 3: Map and sellers -->
  <div class="section hidden" id="sellersMapSection">
    <h2>Nearby Sellers (within <span id="radiusKmDisplay">20</span> km)</h2>
    <div id="sellersCount" class="status" style="display:none"></div>
    <div class="row">
      <input type="search" id="searchInput" placeholder="Search by product, address, or username" style="flex:2;">
      <input type="number" id="radiusInput" value="20" min="1" max="200" style="width:100px;">
      <button class="btn" id="refreshSellersBtn">Search</button>
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
let currentBuyer = { user_id: LOGGED_USER_ID, buyer_id: null };
let currentProfile = null;
let currentCoords = null;
let currentSeller = null;
let map = null;
let allSellers = [];
let selectedListingId = 0;
let inquiryPollInterval = null;
let modalOpen = false;

async function apiRequest(formData) {
  const res = await fetch('buy_handler.php', { method: 'POST', body: formData });
  const text = await res.text();
  try { return JSON.parse(text); } catch(e) { console.error(text); throw new Error('Server error'); }
}

function showStatus(elId, msg, type) { const el = document.getElementById(elId); if(el){ el.textContent=msg; el.className=`status ${type}`; el.style.display='block'; } }
function escapeHtml(str) { return String(str||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

function switchSection(hideId, showId, stepIndex) {
  document.getElementById(hideId).classList.add('hidden');
  document.getElementById(showId).classList.remove('hidden');
  for (let i=1;i<=4;i++) {
    const s=document.getElementById(`step${i}`);
    s.classList.remove('active','completed');
    if(i<stepIndex) s.classList.add('completed');
    else if(i===stepIndex) s.classList.add('active');
  }
  window.scrollTo({top:0,behavior:'smooth'});
  if(showId==='sellersMapSection' && map) setTimeout(()=>map.invalidateSize(),100);
}

// Capture location
document.getElementById('captureLocBtn').onclick = () => {
  if(!navigator.geolocation) return showStatus('locationStatus','Geolocation not supported','error');
  showStatus('locationStatus','Requesting GPS location...','loading');
  navigator.geolocation.getCurrentPosition(
    pos => {
      currentCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      document.getElementById('capturedLat').textContent = currentCoords.lat.toFixed(6);
      document.getElementById('capturedLng').textContent = currentCoords.lng.toFixed(6);
      document.getElementById('coordsDisplay').style.display = 'block';
      showStatus('locationStatus',`✓ Location captured (accuracy ${pos.coords.accuracy.toFixed(0)}m)`,'success');
    },
    err => showStatus('locationStatus','Permission denied – manual address allowed','error'),
    { enableHighAccuracy: true, timeout:10000 }
  );
};

document.getElementById('buyerForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fullname = document.getElementById('fullname').value.trim();
  const email = document.getElementById('email').value.trim();
  const phone = document.getElementById('phone').value.trim();
  const preferences = document.getElementById('preferences').value.trim();
  const address = document.getElementById('location').value.trim();
  if(!fullname || !email || !phone || !preferences) return alert('All fields required');
  if(!currentCoords && !address) return alert('Please capture location or provide manual address');
  currentBuyer = { ...currentBuyer, fullname, email, phone, preferences,
    location_address: address || `${currentCoords.lat},${currentCoords.lng}`,
    latitude: currentCoords?.lat || null, longitude: currentCoords?.lng || null };
  sessionStorage.setItem('tempBuyer', JSON.stringify(currentBuyer));
  document.getElementById('termsLat').textContent = currentCoords?.lat?.toFixed(6) || 'N/A';
  document.getElementById('termsLng').textContent = currentCoords?.lng?.toFixed(6) || 'N/A';
  document.getElementById('termsLocationDisplay').style.display = currentCoords ? 'block' : 'none';
  switchSection('buyerFormSection','termsSection',2);
});

document.getElementById('backToFormBtn').onclick = () => switchSection('termsSection','buyerFormSection',1);
document.getElementById('acceptTermsBtn').onclick = async function() {
  if(!document.getElementById('termsCheck').checked) return alert('Accept consent');
  const stored = sessionStorage.getItem('tempBuyer');
  if(stored) Object.assign(currentBuyer, JSON.parse(stored));
  if(!currentBuyer.user_id) return alert('User ID missing');
  const fd = new FormData();
  fd.append('action','saveBuyer');
  fd.append('fullname', currentBuyer.fullname);
  fd.append('email', currentBuyer.email);
  fd.append('phone', currentBuyer.phone);
  fd.append('location_address', currentBuyer.location_address);
  fd.append('latitude', currentBuyer.latitude??'');
  fd.append('longitude', currentBuyer.longitude??'');
  fd.append('preferences', currentBuyer.preferences);
  fd.append('consent_text','Accepted as displayed');
  fd.append('buyer_agent', navigator.userAgent);
  this.disabled=true; this.textContent='Saving...';
  try {
    const data = await apiRequest(fd);
    if(data.success){
      currentBuyer.buyer_id = data.buyer_id;
      const pfd = new FormData(); pfd.append('action','saveBuyerProfile');
      pfd.append('fullname',currentBuyer.fullname); pfd.append('email',currentBuyer.email); pfd.append('phone',currentBuyer.phone);
      pfd.append('preferences',currentBuyer.preferences||''); pfd.append('location_address',currentBuyer.location_address||'');
      pfd.append('latitude',currentBuyer.latitude??''); pfd.append('longitude',currentBuyer.longitude??'');
      const pres = await apiRequest(pfd);
      if(pres.success){ const gfd=new FormData(); gfd.append('action','getBuyerProfile'); gfd.append('profile_id',pres.profile_id);
        const gres=await apiRequest(gfd); if(gres.success) currentProfile=gres.profile; }
      await loadNearbySellers();
      await loadProfiles();
      switchSection('termsSection','sellersMapSection',3);
      if(inquiryPollInterval) clearInterval(inquiryPollInterval);
      inquiryPollInterval = setInterval(loadBuyerInquiries, 10000);
    } else alert('Error: '+data.error);
  } catch(err){ alert('Network error: '+err.message); }
  finally { this.disabled=false; this.textContent='Accept & Find Sellers →'; }
};

async function loadNearbySellers(){
  if(!currentBuyer.latitude || !currentBuyer.longitude){ document.getElementById('sellersCount').innerHTML='<div class="status error">Location missing</div>'; return; }
  document.getElementById('sellersCount').innerHTML='<div class="status loading">Searching...</div>';
  const radius = parseFloat(document.getElementById('radiusInput').value||20);
  const fd = new FormData(); fd.append('action','getNearSellers'); fd.append('latitude',currentBuyer.latitude); fd.append('longitude',currentBuyer.longitude); fd.append('radius',radius);
  const data = await apiRequest(fd);
  if(data.success){
    document.getElementById('sellersCount').innerHTML = `<div class="status success">✅ Found ${data.count} seller(s)</div>`;
    allSellers = data.data||[];
    renderMap(allSellers);
    renderSellerCards(allSellers);
  } else document.getElementById('sellersCount').innerHTML = `<div class="status error">❌ ${data.error}</div>`;
}

function renderMap(sellers){
  if(map) map.remove();
  if(!currentBuyer.latitude) return;
  map = L.map('mapView').setView([currentBuyer.latitude, currentBuyer.longitude],12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  L.marker([currentBuyer.latitude, currentBuyer.longitude]).addTo(map).bindPopup('📌 You');
  const cluster = L.markerClusterGroup();
  sellers.forEach(s=>{
    const m = L.marker([s.latitude, s.longitude]);
    m.bindPopup(`<b>${escapeHtml(s.description)}</b><br>📍 ${s.distance.toFixed(1)} km<br><a href="#" onclick="viewSellerDetail(${s.user_id})">View</a>`);
    cluster.addLayer(m);
  });
  map.addLayer(cluster);
  setTimeout(()=>map.invalidateSize(),100);
}
window.addEventListener('resize',()=>{ if(map) map.invalidateSize(); });

function renderSellerCards(sellers){
  const grid = document.getElementById('sellersGrid'); grid.innerHTML='';
  sellers.forEach(s=>{
    const card = document.createElement('div'); card.className='seller-card';
    card.innerHTML = `<h3>${escapeHtml(s.description)}</h3><div class="distance">🚗 ${s.distance.toFixed(1)} km</div><p>📍 ${escapeHtml(s.location_address||'')}</p><div class="row"><button class="btn secondary" onclick="viewSellerDetail(${s.user_id})">Details</button><button class="btn success" onclick="viewSellerDetail(${s.user_id})">Contact</button></div>`;
    grid.appendChild(card);
  });
}

async function loadProfiles(){
  const fd = new FormData(); fd.append('action','getBuyerProfiles');
  const data = await apiRequest(fd);
  if(data.success) renderProfilesList(data.data||[]);
}
function renderProfilesList(list){
  const container = document.getElementById('profilesList'); container.innerHTML='';
  if(!list.length){ container.innerHTML='<div class="small">No saved profiles.</div>'; return; }
  list.forEach(p=>{
    const div = document.createElement('div'); div.className='item';
    div.innerHTML = `<div class="thumb" style="width:48px;height:48px;background:#f0f2f6;display:flex;align-items:center;justify-content:center">👤</div>
      <div class="meta"><h4>${escapeHtml(p.fullname)}</h4><p>${escapeHtml(p.email)} | ${escapeHtml(p.phone)}</p><p>${escapeHtml(p.location_address||'')}</p></div>
      <div class="actions"><button class="btn secondary" data-id="${p.profile_id}">Use</button><button class="btn" data-edit="${p.profile_id}">Edit</button><button class="remove" data-del="${p.profile_id}">Delete</button></div>`;
    div.querySelector('[data-id]')?.addEventListener('click', ()=>useProfile(p.profile_id));
    div.querySelector('[data-edit]')?.addEventListener('click', ()=>editProfile(p.profile_id));
    div.querySelector('[data-del]')?.addEventListener('click', async ()=>{
      if(!confirm('Delete?')) return;
      const fd=new FormData(); fd.append('action','deleteBuyerProfile'); fd.append('profile_id',p.profile_id);
      await apiRequest(fd); loadProfiles();
    });
    container.appendChild(div);
  });
}
async function useProfile(profileId){
  const fd = new FormData(); fd.append('action','setActiveProfile'); fd.append('profile_id',profileId);
  await apiRequest(fd);
  const gfd = new FormData(); gfd.append('action','getBuyerProfile'); gfd.append('profile_id',profileId);
  const data = await apiRequest(gfd);
  if(data.success){
    currentProfile = data.profile;
    document.getElementById('fullname').value = currentProfile.fullname;
    document.getElementById('email').value = currentProfile.email;
    document.getElementById('phone').value = currentProfile.phone;
    document.getElementById('preferences').value = currentProfile.preferences||'';
    document.getElementById('location').value = currentProfile.location_address||'';
    if(currentProfile.latitude && currentProfile.longitude){
      currentCoords = { lat: parseFloat(currentProfile.latitude), lng: parseFloat(currentProfile.longitude) };
      document.getElementById('capturedLat').innerText = currentCoords.lat.toFixed(6);
      document.getElementById('capturedLng').innerText = currentCoords.lng.toFixed(6);
      document.getElementById('coordsDisplay').style.display = 'block';
    }
    alert(`Profile activated: ${currentProfile.fullname}`);
    await loadNearbySellers();
    switchSection('profileFormSection','sellersMapSection',3);
  }
}
async function editProfile(profileId){
  const fd = new FormData(); fd.append('action','getBuyerProfile'); fd.append('profile_id',profileId);
  const data = await apiRequest(fd);
  if(data.success){
    const p = data.profile;
    document.getElementById('fullname').value = p.fullname;
    document.getElementById('email').value = p.email;
    document.getElementById('phone').value = p.phone;
    document.getElementById('preferences').value = p.preferences||'';
    document.getElementById('location').value = p.location_address||'';
    if(p.latitude && p.longitude){
      currentCoords = { lat: parseFloat(p.latitude), lng: parseFloat(p.longitude) };
      document.getElementById('capturedLat').innerText = currentCoords.lat.toFixed(6);
      document.getElementById('capturedLng').innerText = currentCoords.lng.toFixed(6);
      document.getElementById('coordsDisplay').style.display = 'block';
    }
    document.getElementById('profileSubmitBtn').setAttribute('data-edit-id',profileId);
    switchSection('sellersMapSection','buyerFormSection',1);
  }
}
document.getElementById('refreshProfilesBtn').onclick = loadProfiles;
document.getElementById('addProfileBtn').onclick = ()=>{
  document.getElementById('buyerForm').reset();
  document.getElementById('coordsDisplay').style.display='none';
  currentCoords = null;
  switchSection('sellersMapSection','buyerFormSection',1);
};
document.getElementById('buyerFormCloseBtn').onclick = ()=>{
  switchSection('buyerFormSection','sellersMapSection',3);
};
document.getElementById('editInfoBtn').onclick = ()=>{
  if(currentProfile) editProfile(currentProfile.profile_id);
  else alert('No active profile');
};

window.viewSellerDetail = async (sellerId) => {
  const fd = new FormData(); fd.append('action','getSellerInfo'); fd.append('seller_id',sellerId);
  const data = await apiRequest(fd);
  if(data.success){
    currentSeller = data.seller; currentSeller.user_id = currentSeller.ids || sellerId;
    currentSeller.listings = data.listings||[];
    displaySellerDetail();
    switchSection('sellersMapSection','sellerDetailSection',4);
  } else alert('Error loading seller');
};
function displaySellerDetail(){
  document.getElementById('sellerTitle').innerHTML = `Seller: ${escapeHtml(currentSeller.User)}`;
  document.getElementById('sellerInfo').innerHTML = `<p><strong>Name:</strong> ${escapeHtml(currentSeller.User)}</p><p>Listings: ${currentSeller.listings.length}</p>`;
  const listDiv = document.getElementById('sellerListings');
  listDiv.innerHTML = currentSeller.listings.map((l,idx)=>`
    <div style="background:#f9fafb;padding:12px;margin:8px 0;border-radius:8px">
      <input type="radio" name="listing" value="${l.location_id}" ${idx===0?'checked':''} onchange="selectedListingId=${l.location_id}">
      <strong>${escapeHtml(l.description)}</strong><br>📞 ${escapeHtml(l.number||'')} 💬 ${escapeHtml(l.socmed||'')}
    </div>`).join('');
  selectedListingId = currentSeller.listings[0]?.location_id || 0;
}
document.getElementById('messageForm').onsubmit = async (e) => {
  e.preventDefault();
  const msg = document.getElementById('messageContent').value.trim();
  if(!msg) return alert('Type a message');
  if(!currentProfile) return alert('No active profile selected');
  if(!currentSeller) return alert('No seller selected');
  if(!selectedListingId) return alert('Select a listing');
  const includeInfo = document.getElementById('includeInfoCheck').checked;
  let buyerInfo = '';
  if(includeInfo) buyerInfo = `Name: ${currentProfile.fullname}\nEmail: ${currentProfile.email}\nPhone: ${currentProfile.phone}`;
  const fd = new FormData();
  fd.append('action','sendMessage');
  fd.append('profile_id', currentProfile.profile_id);
  fd.append('seller_id', currentSeller.user_id);
  fd.append('location_id', selectedListingId);
  fd.append('message', msg);
  fd.append('buyer_info', buyerInfo);
  const data = await apiRequest(fd);
  if(data.success){
    alert('Message sent!');
    document.getElementById('messageContent').value='';
    await loadBuyerInquiries();
    switchSection('sellerDetailSection','sellersMapSection',3);
  } else alert('Failed: '+data.error);
};
document.getElementById('backToSellersBtn').onclick = ()=> switchSection('sellerDetailSection','sellersMapSection',3);
document.getElementById('backToTermsBtn').onclick = ()=> switchSection('sellersMapSection','termsSection',2);
document.getElementById('refreshSellersBtn').onclick = loadNearbySellers;
document.getElementById('refreshInquiriesBtn').onclick = loadBuyerInquiries;
document.getElementById('headerSearchBtn')?.addEventListener('click', async () => {
  const rad = parseFloat(document.getElementById('headerRadiusInput').value||20);
  document.getElementById('radiusInput').value = rad;
  await loadNearbySellers();
  switchSection('buyerFormSection','sellersMapSection',3);
});

async function loadBuyerInquiries(){
  if(!currentProfile) return;
  const fd = new FormData(); fd.append('action','getBuyerInquiries'); fd.append('profile_id',currentProfile.profile_id);
  const data = await apiRequest(fd);
  if(data.success){
    const cont = document.getElementById('inquiriesContainer');
    cont.innerHTML = '';
    if(!data.data.length){ cont.innerHTML='<div class="small">No inquiries yet.</div>'; return; }
    data.data.forEach(iq=>{
      const div = document.createElement('div'); div.className='item';
      div.innerHTML = `<div class="meta"><strong>${escapeHtml(iq.listing_desc||'Listing')}</strong><br>Seller: ${escapeHtml(iq.seller_name)}<br>Status: ${iq.inquiry_status}<br>${new Date(iq.interested_at).toLocaleString()}</div><div class="actions"><button class="btn secondary" onclick="viewSellerDetail(${iq.seller_id})">View</button></div>`;
      cont.appendChild(div);
    });
  }
}

// Initial load
(async ()=>{
  try {
    const fd = new FormData(); fd.append('action','getBuyer');
    const data = await apiRequest(fd);
    if(data.success){
      currentBuyer.buyer_id = data.buyer.buyer_id;
      currentProfile = { profile_id: data.buyer.buyer_id, fullname: data.buyer.fullname, email: data.buyer.email, phone: data.buyer.phone, preferences: data.buyer.preferences, location_address: data.buyer.location_address, latitude: data.buyer.latitude, longitude: data.buyer.longitude };
      if(currentProfile.latitude && currentProfile.longitude){
        currentBuyer.latitude = currentProfile.latitude; currentBuyer.longitude = currentProfile.longitude;
        await loadNearbySellers();
        switchSection('buyerFormSection','sellersMapSection',3);
        setInterval(loadBuyerInquiries, 10000);
      } else { switchSection('buyerFormSection','buyerFormSection',1); }
    } else { await loadProfiles(); }
  } catch(e){ console.warn(e); }
})();
</script>
</body>
</html>