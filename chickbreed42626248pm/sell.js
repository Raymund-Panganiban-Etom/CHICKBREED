
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
    fd.append('csrf_token', document.getElementById('csrf_token').value);
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
// Custom location request with user-friendly guidance
async function requestDeviceLocation() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            locationStatusDiv.innerHTML = '<span class="error">Geolocation not supported by your browser.</span>';
            resolve({});
            return;
        }

        // Show a status message while requesting
        locationStatusDiv.innerHTML = 'Requesting location...';
        locationStatusDiv.style.display = 'block';

        // Function to show a custom dialog for permission denial
        function showPermissionDeniedDialog() {
            const modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:1000;';
            const content = document.createElement('div');
            content.style.cssText = 'background:white;border-radius:20px;padding:1.5rem;max-width:90%;width:320px;text-align:center;';
            content.innerHTML = `
                <h3 style="color:#C62828;">📍 Location Permission Required</h3>
                <p style="margin:1rem 0;">You have denied location access. To use this feature, please enable location in your browser settings.</p>
                <p style="font-size:0.85rem;background:#f0f0f0;padding:8px;border-radius:8px;">
                    <strong>How to enable:</strong><br>
                    Android: Tap the lock icon → Site settings → Location → Allow<br>
                    iOS: Settings → Privacy → Location Services → This site → Allow
                </p>
                <button id="retryLocBtn" class="btn" style="margin-top:1rem;">🔁 Try Again</button>
                <button id="cancelLocBtn" class="btn secondary" style="margin-top:1rem;">✖ Cancel</button>
            `;
            modal.appendChild(content);
            document.body.appendChild(modal);

            document.getElementById('retryLocBtn').onclick = () => {
                modal.remove();
                requestDeviceLocation(); // retry
            };
            document.getElementById('cancelLocBtn').onclick = () => {
                modal.remove();
                locationStatusDiv.innerHTML = '<span class="error">Location permission denied. You can still enter a manual address.</span>';
                resolve({});
            };
        }

        // Request location with a generous timeout for mobile
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                currentCoords = {
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                    accuracy: pos.coords.accuracy
                };
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
                let errorMsg = '';
                switch(err.code) {
                    case err.PERMISSION_DENIED:
                        showPermissionDeniedDialog();
                        return; // don't resolve yet; retry will call again
                    case err.POSITION_UNAVAILABLE:
                        errorMsg = 'GPS signal unavailable. Please turn on location services (GPS) and try again.';
                        break;
                    case err.TIMEOUT:
                        errorMsg = 'Location request timed out. Please try again in a place with better GPS signal.';
                        break;
                    default:
                        errorMsg = 'Location error: ' + err.message;
                }
                locationStatusDiv.innerHTML = `<span class="error">${errorMsg}</span>`;
                resolve({});
            },
            { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
        );
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
  formData.append('csrf_token', document.getElementById('csrf_token').value);
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
    fd.append('csrf_token', document.getElementById('csrf_token').value);
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
    const fd = new FormData(); 
    fd.append('csrf_token', document.getElementById('csrf_token').value);
    fd.append('action', 'dismissNotification'); 
    fd.append('notif_id', notifId);
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
