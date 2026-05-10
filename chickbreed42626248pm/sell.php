<?php
session_start();
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
<title>Seller Listings – FarmConnect</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
  :root{--accent:#0b78d1;--muted:#666;--bg:#f6f8fb}
  *{box-sizing:border-box}
  body{font-family:Inter,Segoe UI,Arial; background:var(--bg); margin:0; padding:20px; display:flex;flex-direction:column;align-items:center;}
  .container{width:720px; max-width:96%;}
  .plus-box{width:84px;height:84px;border-radius:12px;background:var(--accent);color:#fff;font-size:56px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 6px 18px rgba(11,120,209,.18);user-select:none;}
  .plus-box:hover{transform:translateY(-3px);transition:transform .15s}
  .form-card{margin-top:18px;background:#fff;padding:16px;border-radius:10px;box-shadow:0 6px 18px rgba(2,6,23,.06);display:none;}
  label{display:block;font-weight:600;margin-top:8px;color:#222}
  input[type="text"], input[type="tel"], textarea{width:100%;padding:8px;border:1px solid #e2e6ef;border-radius:6px;margin-top:6px;font-size:14px}
  textarea{min-height:72px;resize:vertical}
  .row{display:flex;gap:8px}
  .row > *{flex:1}
  .small{font-size:13px;color:var(--muted);margin-top:6px}
  .btn{background:var(--accent);color:#fff;padding:10px 14px;border-radius:8px;border:0;cursor:pointer;margin-top:12px;display:inline-block;text-align:center;min-height:44px;min-width:44px;}
  .btn.secondary{background:#e9eef8;color:var(--accent);border:1px solid rgba(11,120,209,.12)}
  #list{margin-top:18px;display:flex;flex-direction:column;gap:10px}
  .item{background:#fff;padding:12px;border-radius:10px;display:flex;gap:12px;align-items:flex-start;box-shadow:0 6px 18px rgba(2,6,23,.04);flex-wrap:wrap;}
  .thumb{width:96px;height:72px;border-radius:6px;background:#f0f2f6;object-fit:cover;border:1px solid #eee;max-width:100%;}
  .meta{flex:1;min-width:150px;}
  .meta h4{margin:0 0 6px 0;font-size:16px}
  .meta p{margin:0;color:var(--muted);font-size:13px}
  .actions{display:flex;flex-direction:column;gap:6px}
  .remove{background:#ff6b6b;color:#fff;border:0;padding:6px 8px;border-radius:6px;cursor:pointer}
  .loading{color:var(--muted);text-align:center;padding:12px}
  .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.45);z-index:40}
  .modal-card{width:520px;max-width:94%;background:#fff;padding:18px;border-radius:10px;max-height:90vh;overflow-y:auto;}
  .consent-text{font-size:14px;color:#222;line-height:1.4}
  .consent-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
  .consent-copy{font-size:12px;color:var(--muted);margin-top:8px}
  .location-status{font-size:12px;color:var(--muted);margin-top:8px;padding:8px;background:#f0f2f6;border-radius:4px}
  .error{color:#ff6b6b}
  .success{color:#51cf66}
  .map-container{width:100%;height:400px;border-radius:8px;margin-top:12px;box-shadow:0 2px 8px rgba(0,0,0,.1);display:none}
  #mapView{width:100%;height:100%;border-radius:8px}
  .map-info{background:#f0f2f6;padding:12px;border-radius:6px;margin-top:12px;font-size:13px}
  .map-toggle{background:#0b78d1;color:#fff;padding:8px 12px;border-radius:6px;border:0;cursor:pointer;font-size:12px;margin-top:8px}
  #notificationsSection{margin-top:24px;}
  /* Responsive */
  @media (max-width:768px){
    .container{max-width:100%;padding:0 12px}
    .row{flex-direction:column}
    .thumb{width:72px;height:56px}
    .map-container{height:300px}
    .btn{width:100%}
    .actions{flex-direction:row;justify-content:flex-end}
    .modal-card{width:95%}
  }
  @media (min-width:769px) and (max-width:1024px){
    .container{max-width:95%}
  }
  button, .btn, .remove, .plus-box{min-height:44px;min-width:44px}
  img,svg,iframe{max-width:100%;height:auto}
</style>
</head>
<body>
<div class="container">
  <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <div class="plus-box" id="plusBox" title="Add new item">+</div>
    <div>
      <h2 style="margin:0">Your Items</h2>
      <div class="small">Click the plus to add a new entry. Entries are stored with your consent.</div>
    </div>
  </div>

  <div class="form-card" id="formCard" aria-hidden="true">
    <form id="entryForm">
      <label>Description <span style="font-weight:400;color:var(--muted)">(what you want to record)</span></label>
      <textarea name="description" id="description" required></textarea>
      <div class="row">
        <div>
          <label>Photo <span style="font-weight:400;color:var(--muted)">(optional)</span></label>
          <input type="file" id="photo" accept="image/*">
          <div class="small">Small images recommended.</div>
        </div>
        <div>
          <label>Social Media</label>
          <input type="text" id="socmed" placeholder="@username or profile link">
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

<script>
// Helper: escape HTML
function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

let currentUserId = null;
let map = null, mapMarker = null, currentCoords = null;

// DOM elements
const plusBox = document.getElementById('plusBox');
const formCard = document.getElementById('formCard');
const entryForm = document.getElementById('entryForm');
const submitBtn = document.getElementById('submitBtn');
const cancelBtn = document.getElementById('cancelBtn');
const listEl = document.getElementById('list');
const consentModal = document.getElementById('consentModal');
const consentCheck = document.getElementById('consentCheck');
const consentAccept = document.getElementById('consentAccept');
const consentCancel = document.getElementById('consentCancel');
const consentTextSpan = document.getElementById('consentText');
const locationStatusDiv = document.getElementById('locationStatus');

// ---------- Session ----------
window.addEventListener('DOMContentLoaded', () => {
  fetch('sell_handler.php?action=getSession')
    .then(r => r.json())
    .then(d => {
      if (d.user_id) {
        currentUserId = d.user_id;
        renderList();
        fetchNotifications();
        setInterval(fetchNotifications, 12000);
      } else {
        const uid = prompt('Please enter your User ID:');
        if (uid) {
          currentUserId = parseInt(uid);
          sessionStorage.setItem('sellUserId', uid);
          renderList();
          fetchNotifications();
          setInterval(fetchNotifications, 12000);
        } else {
          listEl.innerHTML = '<div class="small error">Authentication required. Please log in first.</div>';
        }
      }
    })
    .catch(e => console.warn(e));
});

// toggle form
plusBox.addEventListener('click', () => {
  formCard.style.display = formCard.style.display === 'block' ? 'none' : 'block';
});
cancelBtn.addEventListener('click', () => {
  entryForm.reset();
  formCard.style.display = 'none';
});

// load entries
async function loadEntries(){
  if (!currentUserId) return [];
  try {
    const fd = new FormData();
    fd.append('action', 'getEntries');
    fd.append('user_id', currentUserId);
    const res = await fetch('sell_handler.php', {method: 'POST', body: fd});
    const data = await res.json();
    return data.success ? data.data : [];
  } catch(e){ return []; }
}

async function renderList(){
  if (!currentUserId) return;
  listEl.innerHTML = '<div class="loading">Loading entries...</div>';
  try {
    const items = await loadEntries();
    listEl.innerHTML = '';
    if(items.length === 0){
      listEl.innerHTML = '<div class="small" style="padding:12px;background:#fff;border-radius:8px;margin-top:12px">No entries yet. Add one with the plus box.</div>';
      return;
    }
    items.forEach(it => {
      const itemDiv = document.createElement('div'); itemDiv.className = 'item';
      const img = document.createElement('img'); img.className = 'thumb';
      img.src = `sell_handler.php?action=getPhoto&location_id=${it.location_id}`;
      img.onerror = () => { img.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="96" height="72"><rect width="100%" height="100%" fill="%23f0f2f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23b0b6c3" font-size="12">No photo</text></svg>'; };
      const lat = parseFloat(it.latitude);
      const lng = parseFloat(it.longitude);
      const meta = document.createElement('div'); meta.className = 'meta';
      meta.innerHTML = `
        <h4>${escapeHtml(it.description || '(no description)')}</h4>
        <p><strong>Social:</strong> ${escapeHtml(it.socmed || '-')}</p>
        <p><strong>Number:</strong> ${escapeHtml(it.number || '-')}</p>
        <p><strong>Location:</strong> ${escapeHtml(it.location_address || '-')}</p>
        <p><em style="color:var(--muted);font-size:12px">Coordinates: ${(!isNaN(lat) && !isNaN(lng)) ? `(${lat.toFixed(4)}, ${lng.toFixed(4)})` : 'N/A'}</em></p>
        <p><em style="color:var(--muted);font-size:12px">Saved: ${new Date(it.saved_at).toLocaleString()}</em></p>
      `;
      if (!isNaN(lat) && !isNaN(lng)) {
        const mapBtn = document.createElement('button');
        mapBtn.className = 'map-toggle';
        mapBtn.textContent = 'View Map';
        mapBtn.onclick = () => showLocationMap(lat, lng, it.description || 'Location');
        meta.appendChild(mapBtn);
      }
      const actionsDiv = document.createElement('div'); actionsDiv.className = 'actions';
      const removeBtn = document.createElement('button'); removeBtn.className = 'remove'; removeBtn.textContent = 'Remove';
      removeBtn.onclick = () => { if(confirm('Remove this entry?')) deleteEntry(it.location_id); };
      actionsDiv.appendChild(removeBtn);
      itemDiv.append(img, meta, actionsDiv);
      listEl.appendChild(itemDiv);
    });
  } catch(e) { listEl.innerHTML = `<div class="small error">Error loading list: ${e.message}</div>`; }
}

async function deleteEntry(locationId){
  if (!currentUserId) return;
  try {
    const fd = new FormData();
    fd.append('action', 'deleteEntry');
    fd.append('location_id', locationId);
    fd.append('user_id', currentUserId);
    const res = await fetch('sell_handler.php', {method: 'POST', body: fd});
    const data = await res.json();
    if (data.success) renderList();
    else alert('Failed to delete: ' + (data.error || 'Unknown'));
  } catch(e) { alert('Error deleting: ' + e.message); }
}

// ----- Geolocation & consent -----
async function requestDeviceLocation() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      locationStatusDiv.style.display = 'block';
      locationStatusDiv.innerHTML = '<span class="error">Geolocation not supported</span>';
      resolve({});
    } else {
      locationStatusDiv.style.display = 'block';
      locationStatusDiv.innerHTML = 'Requesting location...';
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          currentCoords = { latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: pos.coords.accuracy };
          locationStatusDiv.innerHTML = `<span class="success">✓ Location captured: ${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}</span>`;
          document.getElementById('coordsLat').textContent = pos.coords.latitude.toFixed(6);
          document.getElementById('coordsLon').textContent = pos.coords.longitude.toFixed(6);
          document.getElementById('mapContainer').style.display = 'block';
          document.getElementById('mapInfo').style.display = 'block';
          document.getElementById('mapToggle').style.display = 'inline-block';
          initializeMap(pos.coords.latitude, pos.coords.longitude);
          resolve(currentCoords);
        },
        (err) => {
          locationStatusDiv.innerHTML = `<span class="error">Location request denied or failed</span>`;
          resolve({});
        },
        {enableHighAccuracy: true, timeout: 10000, maximumAge: 0}
      );
    }
  });
}

function initializeMap(lat, lng){
  if (map) map.remove();
  map = L.map('mapView').setView([lat, lng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);
  mapMarker = L.marker([lat, lng]).addTo(map).bindPopup(`<div><strong>Your Location</strong><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}</div>`).openPopup();
  map.invalidateSize();
}
document.getElementById('mapToggle')?.addEventListener('click', function() {
  const cont = document.getElementById('mapContainer');
  if (cont.style.display === 'block') { cont.style.display = 'none'; this.textContent = 'Show Map'; }
  else { cont.style.display = 'block'; this.textContent = 'Hide Map'; if(map) setTimeout(()=>map.invalidateSize(),100); }
});
function showLocationMap(lat, lng, title){
  const modalDiv = document.createElement('div');
  modalDiv.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000';
  const cardDiv = document.createElement('div');
  cardDiv.style.cssText = 'background:#fff;border-radius:10px;width:90%;max-width:600px;padding:16px;position:relative;max-height:80vh;overflow:auto';
  const closeBtn = document.createElement('button');
  closeBtn.textContent = '✕'; closeBtn.style.cssText = 'position:absolute;top:10px;right:12px;background:#e74c3c;color:#fff;border:none;border-radius:30px;width:30px;height:30px;cursor:pointer';
  closeBtn.onclick = () => { if(tempMap) tempMap.remove(); document.body.removeChild(modalDiv); };
  const header = document.createElement('h4'); header.textContent = title || 'Location Map';
  const mapDiv = document.createElement('div'); mapDiv.style.height = '350px'; mapDiv.style.borderRadius = '8px'; mapDiv.style.margin = '12px 0';
  const info = document.createElement('div'); info.style.background = '#f0f2f6'; info.style.padding = '8px'; info.style.borderRadius = '6px';
  info.innerHTML = `<strong>Coordinates:</strong> ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
  cardDiv.append(closeBtn, header, mapDiv, info);
  modalDiv.appendChild(cardDiv); document.body.appendChild(modalDiv);
  let tempMap = L.map(mapDiv).setView([lat, lng], 16);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OSM' }).addTo(tempMap);
  L.marker([lat, lng]).addTo(tempMap).bindPopup(title).openPopup();
  setTimeout(() => tempMap.invalidateSize(), 100);
}

submitBtn.addEventListener('click', async () => {
  if (!currentUserId) { alert('Please log in first.'); return; }
  const desc = document.getElementById('description').value.trim();
  if(!desc){ alert('Please enter a description.'); return; }
  consentCheck.checked = false;
  locationStatusDiv.style.display = 'none';
  consentModal.style.display = 'flex';
});

consentCancel.addEventListener('click', () => {
  consentModal.style.display = 'none';
  if (map) { map.remove(); map = null; }
  document.getElementById('mapContainer').style.display = 'none';
  document.getElementById('mapInfo').style.display = 'none';
  document.getElementById('mapToggle').style.display = 'none';
});

consentAccept.addEventListener('click', async () => {
  if(!consentCheck.checked){ alert('Please check the consent box to proceed.'); return; }
  consentAccept.disabled = true;
  consentAccept.textContent = 'Processing...';
  await requestDeviceLocation();
  const description = document.getElementById('description').value.trim();
  const socmed = document.getElementById('socmed').value.trim();
  const number = document.getElementById('number').value.trim();
  let location_address = document.getElementById('location').value.trim();
  const photoInput = document.getElementById('photo');
  if (!location_address && currentCoords?.latitude) {
    location_address = `${currentCoords.latitude.toFixed(4)}, ${currentCoords.longitude.toFixed(4)}`;
  }
  let photoBase64 = null;
  if(photoInput.files && photoInput.files[0]){
    try {
      const file = photoInput.files[0];
      const reader = new FileReader();
      photoBase64 = await new Promise((resolve, reject) => {
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
      });
    } catch(e){ console.warn(e); }
  }
  const formData = new FormData();
  formData.append('action', 'saveEntry');
  formData.append('user_id', currentUserId);
  formData.append('description', description);
  formData.append('socmed', socmed);
  formData.append('number', number);
  formData.append('location_address', location_address);
  formData.append('latitude', currentCoords?.latitude || '');
  formData.append('longitude', currentCoords?.longitude || '');
  formData.append('photo_base64', photoBase64 || '');
  formData.append('consent_text', consentTextSpan.innerText);
  formData.append('device_info', navigator.userAgent || '');
  try {
    const res = await fetch('sell_handler.php', {method: 'POST', body: formData});
    const data = await res.json();
    if (data.success) {
      consentModal.style.display = 'none';
      entryForm.reset();
      formCard.style.display = 'none';
      if (map) { map.remove(); map = null; }
      document.getElementById('mapContainer').style.display = 'none';
      document.getElementById('mapInfo').style.display = 'none';
      document.getElementById('mapToggle').style.display = 'none';
      await renderList();
      alert('Entry saved successfully with location data.');
    } else {
      alert('Failed to save entry: ' + (data.error || 'Unknown error'));
    }
  } catch(e) { alert('Error saving entry: ' + e.message); }
  consentAccept.disabled = false;
  consentAccept.textContent = 'Accept';
});

// ------------- Notifications (seller) -------------
async function fetchNotifications() {
  try {
    const fd = new FormData(); fd.append('action', 'getNotifications');
    const res = await fetch('sell_handler.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) renderNotifications(data.data || []);
    else document.getElementById('notificationsList').innerText = 'No notifications.';
  } catch(e) { document.getElementById('notificationsList').innerText = 'Error loading notifications'; }
}

function renderNotifications(items) {
  const el = document.getElementById('notificationsList');
  if (!items || items.length === 0) {
    el.innerHTML = '<div class="small">No notifications.</div>';
    document.getElementById('notificationsSection').style.display = 'none';
    return;
  }
  document.getElementById('notificationsSection').style.display = 'block';
  el.innerHTML = '';
  items.forEach(n => {
    const row = document.createElement('div');
    row.style.cssText = 'background:#fff;padding:12px;border-radius:8px;margin:8px 0;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;';
    row.innerHTML = `
      <div style="flex:1; min-width:150px;">
        <strong>Buyer:</strong> ${escapeHtml(n.buyer_name || 'Unknown')}<br>
        <strong>Message:</strong> ${escapeHtml(n.message_summary || '')}<br>
        <strong>Buyer User ID:</strong> ${escapeHtml(n.buyer_user_id || '')} ${n.buyer_username ? '('+escapeHtml(n.buyer_username)+')' : ''}<br>
        <strong>Listing:</strong> ${escapeHtml(n.listing_desc || '')}
      </div>
      <div style="display:flex;flex-direction:column;gap:8px; min-width:100px;">
        <button class="btn" data-action="accept" data-inquiry="${n.inquiry_id}">Accept</button>
        <button class="btn secondary" data-action="ignore" data-inquiry="${n.inquiry_id}">Ignore</button>
        <button class="btn secondary" data-action="view" data-inquiry="${n.inquiry_id}">View</button>
        <button class="btn secondary" style="background:#f3f4f6;color:#111;border:1px solid #ddd" data-action="dismiss" data-notif="${n.notif_id}">Dismiss</button>
      </div>
    `;
    row.querySelector('[data-action="accept"]')?.addEventListener('click', () => respondInquiry(n.inquiry_id, 'accept'));
    row.querySelector('[data-action="ignore"]')?.addEventListener('click', () => respondInquiry(n.inquiry_id, 'ignore'));
    row.querySelector('[data-action="view"]')?.addEventListener('click', () => viewConversation(n.inquiry_id));
    row.querySelector('[data-action="dismiss"]')?.addEventListener('click', () => dismissNotification(n.notif_id));
    el.appendChild(row);
  });
}

let modal = null;
function showConvModal(html) {
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'convModal';
    modal.className = 'modal';
    modal.innerHTML = `<div class="modal-card"><div id="convContent"></div><div style="text-align:right;margin-top:12px"><button class="btn secondary" id="convClose">Close</button></div></div>`;
    document.body.appendChild(modal);
    document.getElementById('convClose').onclick = () => { modal.style.display = 'none'; };
  }
  document.getElementById('convContent').innerHTML = html;
  modal.style.display = 'flex';
}

async function viewConversation(inquiryId) {
  try {
    const fd = new FormData(); fd.append('action', 'getInquiryMessages'); fd.append('inquiry_id', inquiryId);
    const res = await fetch('sell_handler.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) return alert('Failed to load conversation: ' + (data.error || 'Unknown'));
    const msgs = data.messages || [];
    const profile = data.profile;
    let html = `<h4>Conversation (Inquiry ${inquiryId})</h4>`;
    if (profile) html += `<div style="margin-bottom:8px"><strong>Profile:</strong> ${escapeHtml(profile.fullname || '')} ${escapeHtml(profile.email||'')} ${escapeHtml(profile.phone||'')}</div>`;
    if (msgs.length === 0) html += '<div class="small">No messages yet.</div>';
    msgs.forEach(m => {
      const who = m.sender_type === 'buyer' ? (m.buyer_username ? escapeHtml(m.buyer_username) + ' (buyer)' : 'Buyer') : 'You (seller)';
      html += `<div style="padding:8px;border-radius:6px;margin-bottom:6px;background:#f7f9fc"><div style="font-size:13px;color:#333"><strong>${who}</strong> <small style="color:#666">${new Date(m.sent_at).toLocaleString()}</small></div><div style="margin-top:6px;white-space:pre-wrap">${escapeHtml(m.message_content)}</div></div>`;
    });
    html += `<div style="margin-top:8px"><button class="btn" onclick="respondInquiry(${inquiryId}, 'accept')">Accept</button> <button class="btn secondary" onclick="respondInquiry(${inquiryId}, 'ignore')">Ignore</button></div>`;
    showConvModal(html);
  } catch (e) { alert('Error loading conversation: ' + e.message); }
}

async function respondInquiry(inquiryId, mode) {
  const reply = mode === 'accept' ? prompt('Optional message to buyer (confirm acceptance)') || '' : (prompt('Optional reason for ignoring') || '');
  try {
    const fd = new FormData();
    fd.append('action', 'respondInquiry');
    fd.append('inquiry_id', inquiryId);
    fd.append('mode', mode);
    fd.append('response', reply);
    const res = await fetch('sell_handler.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      alert('Response sent');
      fetchNotifications();
      if (modal) modal.style.display = 'none';
    } else alert('Failed: ' + (data.error || 'Unknown'));
  } catch (e) { alert('Error: ' + e.message); }
}

async function dismissNotification(notifId) {
  try {
    const fd = new FormData(); fd.append('action', 'dismissNotification'); fd.append('notif_id', notifId);
    const res = await fetch('sell_handler.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) fetchNotifications();
    else alert('Failed to dismiss');
  } catch (e) { alert('Error: ' + e.message); }
}

function readFileAsDataURL(file){
  return new Promise((resolve,reject)=>{
    const fr = new FileReader();
    fr.onload = ()=> resolve(fr.result);
    fr.onerror = ()=> reject(fr.error);
    fr.readAsDataURL(file);
  });
}
</script>
</body>
</html>