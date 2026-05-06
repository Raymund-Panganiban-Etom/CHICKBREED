<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Seller Listings (Database Powered)</title>
<!-- Leaflet Map Library (OpenStreetMap) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
  :root{--accent:#0b78d1;--muted:#666;--bg:#f6f8fb}
  body{font-family:Inter,Segoe UI,Arial; background:var(--bg); margin:0; padding:28px; display:flex;flex-direction:column;align-items:center;}
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
  .btn{background:var(--accent);color:#fff;padding:10px 14px;border-radius:8px;border:0;cursor:pointer;margin-top:12px}
  .btn.secondary{background:#e9eef8;color:var(--accent);border:1px solid rgba(11,120,209,.12)}
  #list{margin-top:18px;display:flex;flex-direction:column;gap:10px}
  .item{background:#fff;padding:12px;border-radius:10px;display:flex;gap:12px;align-items:flex-start;box-shadow:0 6px 18px rgba(2,6,23,.04)}
  .thumb{width:96px;height:72px;border-radius:6px;background:#f0f2f6;object-fit:cover;border:1px solid #eee}
  .meta{flex:1}
  .meta h4{margin:0 0 6px 0;font-size:16px}
  .meta p{margin:0;color:var(--muted);font-size:13px}
  .actions{display:flex;flex-direction:column;gap:6px}
  .remove{background:#ff6b6b;color:#fff;border:0;padding:6px 8px;border-radius:6px;cursor:pointer}
  .loading{color:var(--muted);text-align:center;padding:12px}
  /* consent modal */
  .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.45);z-index:40}
  .modal-card{width:520px;max-width:94%;background:#fff;padding:18px;border-radius:10px}
  .consent-text{font-size:14px;color:#222;line-height:1.4}
  .consent-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
  .consent-copy{font-size:12px;color:var(--muted);margin-top:8px}
  .location-status{font-size:12px;color:var(--muted);margin-top:8px;padding:8px;background:#f0f2f6;border-radius:4px}
  .error{color:#ff6b6b}
  .success{color:#51cf66}
  /* Map styles */
  .map-container{width:100%;height:400px;border-radius:8px;margin-top:12px;box-shadow:0 2px 8px rgba(0,0,0,.1);display:none}
  #mapView{width:100%;height:100%;border-radius:8px}
  .map-info{background:#f0f2f6;padding:12px;border-radius:6px;margin-top:12px;font-size:13px}
  .map-info p{margin:4px 0}
  .map-toggle{background:#0b78d1;color:#fff;padding:8px 12px;border-radius:6px;border:0;cursor:pointer;font-size:12px;margin-top:8px}
  .map-toggle:hover{background:#0960a3}
  @media (max-width:560px){ .row{flex-direction:column} .thumb{width:72px;height:56px} .map-container{height:300px} }
</style>
</head>
<body>
  <div class="container">
    <div style="display:flex;align-items:center;gap:14px;">
      <div class="plus-box" id="plusBox" title="Add new item">+</div>
      <div>
        <h2 style="margin:0">Your Items</h2>
        <div class="small">Click the plus to add a new entry. Entries are stored in our database with your consent.</div>
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
  </div>

  <!-- Consent modal -->
  <div class="modal" id="consentModal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-card">
      <h3 style="margin-top:0">Consent under Republic Act No. 10173</h3>
      <div class="consent-text" id="consentText">
        By clicking <strong>Accept</strong> you give your explicit consent to the collection and processing of the personal data you provided (including photo and location) for the purpose of recording this entry. Your location will be automatically captured from your device. This consent is recorded in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173). You may request access, correction, or deletion of your data.
      </div>
      <label style="display:block;margin-top:10px"><input type="checkbox" id="consentCheck"> I have read and accept the terms and privacy statement above</label>
      <div id="locationStatus" class="location-status" style="display:none;"></div>
      <div class="map-container" id="mapContainer">
        <div id="mapView"></div>
      </div>
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
/* Database-powered implementation:
   - Stores entries in Chickacc database (locations table)
   - Captures device location on consent acceptance
   - Uses user_id from session/authentication
*/

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
const consentText = document.getElementById('consentText');
const locationStatus = document.getElementById('locationStatus');

// Map variables
let map = null;
let mapMarker = null;
let currentCoords = null;

// Get user_id from session (stored in PHP $_SESSION)
let currentUserId = null;

// Initialize - get user ID from backend
window.addEventListener('DOMContentLoaded', () => {
  // Try to get user_id from session via a small PHP request
  fetch('sell_handler.php?action=getSession')
    .then(r => r.json())
    .then(d => {
      if (d.user_id) {
        currentUserId = d.user_id;
        renderList();
      } else {
        // No session, prompt for login or user ID
        const uid = prompt('Please enter your User ID:');
        if (uid) {
          currentUserId = parseInt(uid);
          sessionStorage.setItem('sellUserId', uid);
          renderList();
        } else {
          listEl.innerHTML = '<div class="small error">Authentication required. Please log in first.</div>';
        }
      }
    })
    .catch(e => {
      console.log('Session fetch failed, checking sessionStorage');
      const stored = sessionStorage.getItem('sellUserId');
      if (stored) {
        currentUserId = parseInt(stored);
        renderList();
      }
    });
});

// toggle form
plusBox.addEventListener('click', () => {
  formCard.style.display = formCard.style.display === 'block' ? 'none' : 'block';
  formCard.setAttribute('aria-hidden', formCard.style.display !== 'block');
});

// cancel clears form and hides
cancelBtn.addEventListener('click', () => {
  entryForm.reset();
  formCard.style.display = 'none';
});

// Load entries from database
async function loadEntries(){
  if (!currentUserId) return [];
  try {
    const formData = new FormData();
    formData.append('action', 'getEntries');
    formData.append('user_id', currentUserId);
    
    const res = await fetch('sell_handler.php', {method: 'POST', body: formData});
    const data = await res.json();
    return data.success ? data.data : [];
  } catch(e){ 
    console.error('Failed to load entries:', e);
    return []; 
  }
}

// Render list from database
// Render list from database - FIXED
// FIXED renderList with better error handling
async function renderList(){
  if (!currentUserId) {
    console.error("No currentUserId");
    return;
  }
  
  listEl.innerHTML = '<div class="loading">Loading entries...</div>';
  
  try {
    const items = await loadEntries();
    listEl.innerHTML = '';
    
    if(items.length === 0){
      listEl.innerHTML = '<div class="small" style="padding:12px;background:#fff;border-radius:8px;margin-top:12px">No entries yet. Add one with the plus box.</div>';
      return;
    }
    
    items.forEach((it) => {
      const item = document.createElement('div'); item.className = 'item';
      const img = document.createElement('img'); img.className = 'thumb';
      
      img.src = it.photo_name 
        ? `sell_handler.php?action=getPhoto&location_id=${it.location_id}&user_id=${currentUserId}`
        : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="96" height="72"><rect width="100%" height="100%" fill="%23f0f2f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23b0b6c3" font-size="12">No photo</text></svg>';

      const lat = parseFloat(it.latitude);
      const lng = parseFloat(it.longitude);

      const meta = document.createElement('div'); 
      meta.className = 'meta';
      meta.innerHTML = `
        <h4>${escapeHtml(it.description || '(no description)')}</h4>
        <p><strong>Social:</strong> ${escapeHtml(it.socmed || '-')}</p>
        <p><strong>Number:</strong> ${escapeHtml(it.number || '-')}</p>
        <p><strong>Location:</strong> ${escapeHtml(it.location_address || '-')}</p>
        <p><em style="color:var(--muted);font-size:12px">
          Coordinates: ${(!isNaN(lat) && !isNaN(lng)) ? `(${lat.toFixed(4)}, ${lng.toFixed(4)})` : 'N/A'}
        </em></p>
        <p><em style="color:var(--muted);font-size:12px">Saved: ${new Date(it.saved_at).toLocaleString()}</em></p>
      `;

      if (!isNaN(lat) && !isNaN(lng)) {
        const mapBtn = document.createElement('button');
        mapBtn.className = 'map-toggle';
        mapBtn.textContent = 'View Map';
        mapBtn.onclick = () => showLocationMap(lat, lng, it.description || 'Location');
        meta.appendChild(mapBtn);
      }

      const actions = document.createElement('div'); 
      actions.className = 'actions';
      const remove = document.createElement('button'); 
      remove.className = 'remove'; 
      remove.textContent = 'Remove';
      remove.onclick = () => { if(confirm('Remove this entry?')) deleteEntry(it.location_id); };
      actions.appendChild(remove);

      item.append(img, meta, actions);
      listEl.appendChild(item);
    });
  } catch(e) {
    console.error("RenderList Error:", e);
    listEl.innerHTML = `<div class="small error">Error loading list: ${e.message}</div>`;
  }
}

// Delete entry from database
async function deleteEntry(locationId) {
  if (!currentUserId) return;
  try {
    const formData = new FormData();
    formData.append('action', 'deleteEntry');
    formData.append('location_id', locationId);
    formData.append('user_id', currentUserId);
    
    const res = await fetch('sell_handler.php', {method: 'POST', body: formData});
    const data = await res.json();
    if (data.success) {
      renderList();
    } else {
      alert('Failed to delete entry: ' + (data.error || 'Unknown error'));
    }
  } catch(e) {
    alert('Error deleting entry: ' + e.message);
  }
}

// Escape helper
function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

// Request device location
async function requestDeviceLocation() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      locationStatus.style.display = 'block';
      locationStatus.innerHTML = '<span class="error">Geolocation not supported</span>';
      resolve({});
    } else {
      locationStatus.style.display = 'block';
      locationStatus.innerHTML = 'Requesting location...';
      
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          currentCoords = {
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            accuracy: pos.coords.accuracy
          };
          
          locationStatus.innerHTML = `<span class="success">✓ Location captured: ${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}</span>`;
          
          // Update coordinate display
          document.getElementById('coordsLat').textContent = pos.coords.latitude.toFixed(6);
          document.getElementById('coordsLon').textContent = pos.coords.longitude.toFixed(6);
          
          // Show map and info
          document.getElementById('mapContainer').style.display = 'block';
          document.getElementById('mapInfo').style.display = 'block';
          document.getElementById('mapToggle').style.display = 'inline-block';
          
          // Initialize map
          initializeMap(pos.coords.latitude, pos.coords.longitude);
          
          resolve(currentCoords);
        },
        (err) => {
          locationStatus.innerHTML = `<span class="error">Location request denied or failed</span>`;
          console.warn('Geolocation error:', err);
          resolve({});
        },
        {enableHighAccuracy: true, timeout: 10000, maximumAge: 0}
      );
    }
  });
}

// Initialize Leaflet map
function initializeMap(lat, lng) {
  // Destroy existing map if any
  if (map) {
    map.remove();
    map = null;
    mapMarker = null;
  }
  
  // Create new map
  map = L.map('mapView').setView([lat, lng], 15);
  
  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map);
  
  // Add marker at location
  mapMarker = L.marker([lat, lng], {
    icon: L.icon({
      iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
      shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    })
  }).addTo(map);
  
  // Add popup with location info
  mapMarker.bindPopup(`
    <div style="font-size:12px;">
      <strong>Your Location</strong><br>
      Lat: ${lat.toFixed(6)}<br>
      Lng: ${lng.toFixed(6)}<br>
      <em>Accuracy: ${currentCoords.accuracy?.toFixed(0) || 'N/A'} meters</em>
    </div>
  `).openPopup();
  
  // Fit map bounds
  map.invalidateSize();
}

// Toggle map visibility
document.getElementById('mapToggle').addEventListener('click', function() {
  const container = document.getElementById('mapContainer');
  if (container.style.display === 'block') {
    container.style.display = 'none';
    this.textContent = 'Show Map';
  } else {
    container.style.display = 'block';
    this.textContent = 'Hide Map';
    // Refresh map size after display
    if (map) {
      setTimeout(() => map.invalidateSize(), 100);
    }
  }
});

// Show location map in modal/popup
function showLocationMap(lat, lng, title) {
  // Create modal
  const modal = document.createElement('div');
  modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:50';
  
  const modalCard = document.createElement('div');
  modalCard.style.cssText = 'background:#fff;border-radius:10px;width:90%;max-width:600px;max-height:80vh;padding:16px;box-shadow:0 10px 40px rgba(0,0,0,.2);overflow:auto';
  
  const closeBtn = document.createElement('button');
  closeBtn.textContent = '✕';
  closeBtn.style.cssText = 'position:absolute;top:12px;right:12px;background:#ff6b6b;color:#fff;border:0;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:20px';
  closeBtn.onclick = () => {
    document.body.removeChild(modal);
    if (tempMap) {
      tempMap.remove();
      tempMap = null;
    }
  };
  
  const header = document.createElement('h3');
  header.textContent = title || 'Location Map';
  header.style.margin = '0 0 12px 0';
  
  const mapContainer = document.createElement('div');
  mapContainer.id = 'tempMapView';
  mapContainer.style.cssText = 'width:100%;height:400px;border-radius:8px;margin:12px 0';
  
  const info = document.createElement('div');
  info.style.cssText = 'background:#f0f2f6;padding:12px;border-radius:6px;font-size:13px';
  info.innerHTML = `
    <p><strong>Location Coordinates:</strong></p>
    <p>Latitude: <strong>${lat.toFixed(6)}</strong></p>
    <p>Longitude: <strong>${lng.toFixed(6)}</strong></p>
    <p style="color:#666;font-size:12px;margin-top:8px">Map powered by OpenStreetMap</p>
  `;
  
  modalCard.appendChild(closeBtn);
  modalCard.appendChild(header);
  modalCard.appendChild(mapContainer);
  modalCard.appendChild(info);
  modal.appendChild(modalCard);
  document.body.appendChild(modal);
  
  // Initialize temporary map
  let tempMap = null;
  setTimeout(() => {
    tempMap = L.map('tempMapView').setView([lat, lng], 16);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(tempMap);
    
    L.marker([lat, lng], {
      icon: L.icon({
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      })
    }).addTo(tempMap).bindPopup(`<strong>${escapeHtml(title)}</strong>`).openPopup();
    
    tempMap.invalidateSize();
  }, 100);
}

// When user clicks Submit -> open consent modal
submitBtn.addEventListener('click', async () => {
  if (!currentUserId) {
    alert('Please log in first.');
    return;
  }
  
  const desc = document.getElementById('description').value.trim();
  if(!desc){ alert('Please enter a description.'); return; }
  
  consentCheck.checked = false;
  locationStatus.style.display = 'none';
  consentModal.style.display = 'flex';
  consentModal.setAttribute('aria-hidden','false');
});

// Consent cancel
consentCancel.addEventListener('click', () => {
  consentModal.style.display = 'none';
  consentModal.setAttribute('aria-hidden','true');
  locationStatus.style.display = 'none';
  
  // Cleanup map
  if (map) {
    map.remove();
    map = null;
    mapMarker = null;
  }
  document.getElementById('mapContainer').style.display = 'none';
  document.getElementById('mapInfo').style.display = 'none';
  document.getElementById('mapToggle').style.display = 'none';
});

// Consent accept -> capture location, save to database
consentAccept.addEventListener('click', async () => {
  if(!consentCheck.checked){ alert('Please check the consent box to proceed.'); return; }
  
  consentAccept.disabled = true;
  consentAccept.textContent = 'Processing...';
  
  // Request device location
  await requestDeviceLocation();
  
  // Gather form fields
  const description = document.getElementById('description').value.trim();
  const socmed = document.getElementById('socmed').value.trim();
  const number = document.getElementById('number').value.trim();
  let location_address = document.getElementById('location').value.trim();
  const photoInput = document.getElementById('photo');

  // If no manual location, try to use captured coordinates
  if (!location_address && currentCoords?.latitude) {
    location_address = `${currentCoords.latitude.toFixed(4)}, ${currentCoords.longitude.toFixed(4)}`;
  }

  // Read photo as base64 if present
  let photoBase64 = null;
  if(photoInput.files && photoInput.files[0]){
    try {
      photoBase64 = await readFileAsDataURL(photoInput.files[0]);
    } catch(e){
      console.warn('Photo read failed', e);
    }
  }

  // Build form data
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
  formData.append('consent_text', consentText.innerText);
  formData.append('device_info', navigator.userAgent || '');

  try {
    const res = await fetch('sell_handler.php', {method: 'POST', body: formData});
    const data = await res.json();
    
    if (data.success) {
      consentModal.style.display = 'none';
      consentModal.setAttribute('aria-hidden','true');
      entryForm.reset();
      formCard.style.display = 'none';
      
      // Cleanup map
      if (map) {
        map.remove();
        map = null;
        mapMarker = null;
      }
      document.getElementById('mapContainer').style.display = 'none';
      document.getElementById('mapInfo').style.display = 'none';
      document.getElementById('mapToggle').style.display = 'none';
      
      await renderList();
      alert('Entry saved successfully with location data.');
    } else {
      alert('Failed to save entry: ' + (data.error || 'Unknown error'));
    }
  } catch(e) {
    alert('Error saving entry: ' + e.message);
  }
  
  consentAccept.disabled = false;
  consentAccept.textContent = 'Accept';
});

// Utility: read file as base64 data URL
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
