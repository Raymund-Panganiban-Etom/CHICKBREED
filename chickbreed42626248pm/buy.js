const csrfToken = document.getElementById('csrf_token').value;
let currentBuyer = { user_id: LOGGED_USER_ID, buyer_id: null };
let currentProfile = null;
let currentCoords = null;
let currentSeller = null;
let map = null;
let allSellers = [];
let filteredSellers = [];
let selectedListingId = 0;
let inquiryPollInterval = null;
let modalOpen = false;

// Helper API request (calls buy_handler.php for non‑search actions)
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
  fd.append('csrf_token', csrfToken);
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
        const gres=await apiRequest(gfd); if(gres.success) currentProfile = gres.profile; }
      await loadNearbySellers();
      await loadProfiles();
      switchSection('termsSection','sellersMapSection',3);
      if(inquiryPollInterval) clearInterval(inquiryPollInterval);
      inquiryPollInterval = setInterval(loadBuyerInquiries, 10000);
    } else alert('Error: '+data.error);
  } catch(err){ alert('Network error: '+err.message); }
  finally { this.disabled=false; this.textContent='Accept & Find Sellers →'; }
};

// ---------- LOAD SELLERS (now exclusively uses searchSellers.php) ----------
async function loadNearbySellers() {
  if (!currentBuyer.latitude || !currentBuyer.longitude) {
    document.getElementById('sellersCount').innerHTML = '<div class="status error">Location missing</div>';
    return;
  }
  document.getElementById('sellersCount').innerHTML = '<div class="status loading">Searching for sellers...</div>';
  const radius = parseFloat(document.getElementById('radiusInput').value || 20);
  const keyword = document.getElementById('searchInput').value.trim();

  const fd = new FormData();
  fd.append('latitude', currentBuyer.latitude);
  fd.append('longitude', currentBuyer.longitude);
  fd.append('radius', radius);
  fd.append('keyword', keyword);

  try {
    const res = await fetch('searchSellers.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      document.getElementById('sellersCount').innerHTML =
        `<div class="status success">✅ Found ${data.count} seller(s) near you</div>`;
      allSellers = data.data || [];
      // Map fuzzy coordinates and distance for rest of code
      allSellers.forEach(s => {
        s.latitude  = s.fuzzy_lat;
        s.longitude = s.fuzzy_lng;
        s.distance  = s.distance_km;
      });
      applyFilters();
    } else {
      document.getElementById('sellersCount').innerHTML =
        `<div class="status error">❌ ${data.error}</div>`;
    }
  } catch (err) {
    document.getElementById('sellersCount').innerHTML =
      '<div class="status error">Network error while searching sellers</div>';
    console.error(err);
  }
}

// ---------- FILTER SELLERS ----------
function applyFilters() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
  const radius = parseFloat(document.getElementById('radiusInput').value || 20);

  filteredSellers = allSellers.filter(seller => {
    if (seller.distance > radius) return false;
    if (searchTerm === "") return true;
    const haystack = (
      seller.description + " " +
      (seller.location_address || "") + " " +
      (seller.seller_name || "") + " " +
      (seller.socmed || "") + " " +
      (seller.number || "")
    ).toLowerCase();
    return haystack.includes(searchTerm);
  });

  renderMap(filteredSellers);
  renderSellerCards(filteredSellers);
  document.getElementById('sellersCount').innerHTML =
    `<div class="status success">✅ Showing ${filteredSellers.length} of ${allSellers.length} seller(s)</div>`;
}

// ---------- RENDER MAP (only fuzzy coordinates) ----------
function renderMap(sellers) {
  if (map) map.remove();
  if (!currentBuyer.latitude) return;
  map = L.map('mapView').setView([currentBuyer.latitude, currentBuyer.longitude], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  L.marker([currentBuyer.latitude, currentBuyer.longitude]).addTo(map).bindPopup('📌 You (approximate)');

  const cluster = L.markerClusterGroup();
  sellers.forEach(s => {
    const m = L.marker([s.fuzzy_lat, s.fuzzy_lng]);
    m.bindPopup(`
      <b>${escapeHtml(s.description)}</b><br>
      📍 ~${s.distance.toFixed(1)} km<br>
      <a href="#" onclick="viewSellerDetail(${s.user_id})">View</a>
    `);
    cluster.addLayer(m);
  });
  map.addLayer(cluster);
  setTimeout(() => map.invalidateSize(), 100);
}

// ---------- RENDER SELLER CARDS ----------
function renderSellerCards(sellers) {
  const grid = document.getElementById('sellersGrid');
  grid.innerHTML = '';
  if (sellers.length === 0) {
    grid.innerHTML = '<div class="status warning">No sellers match your search. Try different keywords or increase radius.</div>';
    return;
  }
  sellers.forEach(s => {
    const card = document.createElement('div');
    card.className = 'seller-card';
    card.innerHTML = `
      <h3>${escapeHtml(s.description)}</h3>
      <div class="distance">🚗 ~${s.distance.toFixed(1)} km away</div>
      <p>📍 ${escapeHtml(s.location_address || 'No address')}</p>
      <div class="row">
        <button class="btn secondary" onclick="viewSellerDetail(${s.user_id})">Details</button>
        <button class="btn success" onclick="viewSellerDetail(${s.user_id})">Contact</button>
      </div>
    `;
    grid.appendChild(card);
  });
}

// ---------- VIEW SELLER DETAIL ----------
window.viewSellerDetail = async (sellerId) => {
  const fd = new FormData();
  fd.append('action', 'getSellerInfo');
  fd.append('seller_id', sellerId);
  const data = await apiRequest(fd);
  if (data.success) {
    currentSeller = data.seller;
    currentSeller.user_id = currentSeller.ids || sellerId;
    currentSeller.listings = data.listings || [];
    // Backend already sanitized, but safety delete any that slipped through
    if (currentSeller.listings) {
      currentSeller.listings.forEach(l => {
        delete l.latitude;
        delete l.longitude;
        delete l.exact_lat_encrypted;
        delete l.exact_lng_encrypted;
      });
    }
    displaySellerDetail();
    switchSection('sellersMapSection', 'sellerDetailSection', 4);
  } else {
    alert('Error loading seller');
  }
};

function displaySellerDetail() {
  document.getElementById('sellerTitle').innerHTML = `Seller: ${escapeHtml(currentSeller.User)}`;
  document.getElementById('sellerInfo').innerHTML =
    `<p><strong>Name:</strong> ${escapeHtml(currentSeller.User)}</p>
     <p>Listings: ${currentSeller.listings.length}</p>`;
  const listDiv = document.getElementById('sellerListings');
  listDiv.innerHTML = currentSeller.listings.map((l, idx) => `
    <div style="background:#f9fafb;padding:12px;margin:8px 0;border-radius:8px">
      <input type="radio" name="listing" value="${l.location_id}" ${idx===0?'checked':''} onchange="selectedListingId=${l.location_id}">
      <strong>${escapeHtml(l.description)}</strong><br>
      📞 ${escapeHtml(l.number||'')}  |  💬 ${escapeHtml(l.socmed||'')}
    </div>`).join('');
  selectedListingId = currentSeller.listings[0]?.location_id || 0;
}

// ---------- MESSAGE SUBMIT ----------
document.getElementById('messageForm').onsubmit = async (e) => {
  e.preventDefault();
  const msg = document.getElementById('messageContent').value.trim();
  if (!msg) return alert('Type a message');
  if (!currentProfile) return alert('No active profile selected');
  if (!currentSeller) return alert('No seller selected');
  if (!selectedListingId) return alert('Select a listing');
  const includeInfo = document.getElementById('includeInfoCheck').checked;
  let buyerInfo = '';
  if (includeInfo) buyerInfo = `Name: ${currentProfile.fullname}\nEmail: ${currentProfile.email}\nPhone: ${currentProfile.phone}`;
  const fd = new FormData();
  fd.append('action', 'sendMessage');
  fd.append('profile_id', currentProfile.profile_id);
  fd.append('seller_id', currentSeller.user_id);
  fd.append('location_id', selectedListingId);
  fd.append('message', msg);
  fd.append('buyer_info', buyerInfo);
  const data = await apiRequest(fd);
  if (data.success) {
    alert('Message sent!');
    document.getElementById('messageContent').value = '';
    await loadBuyerInquiries();
    switchSection('sellerDetailSection', 'sellersMapSection', 3);
  } else {
    alert('Failed: ' + data.error);
  }
};

// ---------- PROFILES (unchanged except one section fix) ----------
async function loadProfiles() {
  const fd = new FormData(); 
  fd.append('action','getBuyerProfiles');
  fd.append('csrf_token', document.getElementById('csrf_token').value);
  const data = await apiRequest(fd);
  if(data.success) renderProfilesList(data.data||[]);
}

// Keep your existing renderProfilesList(), useProfile(), editProfile(), etc. exactly as they were.
// Just ensure that inside useProfile(), the switchSection call uses 'buyerFormSection' not 'profileFormSection'.

async function useProfile(profileId) {
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
    switchSection('buyerFormSection','sellersMapSection',3);  // FIX: was 'profileFormSection'
  }
}

// ... keep all other listeners (backToSellersBtn, backToTermsBtn, applyFilterBtn, etc.) unchanged ...

// Initial load (unchanged)
(async () => {
  try {
    const fd = new FormData();
    fd.append('action', 'getBuyer');
    const data = await apiRequest(fd);
    if (data.success) {
      currentBuyer.buyer_id = data.buyer.buyer_id;
      currentProfile = {
        profile_id: data.buyer.buyer_id,
        fullname: data.buyer.fullname,
        email: data.buyer.email,
        phone: data.buyer.phone,
        preferences: data.buyer.preferences,
        location_address: data.buyer.location_address,
        latitude: data.buyer.latitude,
        longitude: data.buyer.longitude
      };
      if (currentProfile.latitude && currentProfile.longitude) {
        currentBuyer.latitude = currentProfile.latitude;
        currentBuyer.longitude = currentProfile.longitude;
        await loadNearbySellers();
        switchSection('buyerFormSection', 'sellersMapSection', 3);
        setInterval(loadBuyerInquiries, 10000);
      } else {
        switchSection('sellersMapSection', 'buyerFormSection', 1);
      }
    } else {
      await loadProfiles();
    }
    await loadProfiles();
  } catch (e) {
    console.warn(e);
  }
})();