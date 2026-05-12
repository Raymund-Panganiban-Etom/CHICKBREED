<?php
// buyer.php - buyer finds nearby listings and can message seller
session_start();
require 'db.php';
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
  header('Location: login.php'); exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Buyer — Find Sellers</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>body{font-family:Arial;padding:12px} #map{height:520px;margin-top:12px}</style>
</head>
<body>
  <h2>Buyer — Find Sellers Nearby</h2>
  <div>
    <label>Search radius (km): <input id="radius" type="number" value="5" min="1" max="100"></label>
    <button id="findBtn">Find Sellers (consent required)</button>
  </div>

  <div id="map" style="display:none"></div>
  <div id="list"></div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const findBtn = document.getElementById('findBtn');
    const radiusInput = document.getElementById('radius');
    const mapDiv = document.getElementById('map');
    const listDiv = document.getElementById('list');
    let map, userMarker, sellerMarkers = [];

    findBtn.addEventListener('click', ()=> {
      if (!confirm('Do you accept sharing your location to find nearby sellers?')) return;
      if (!navigator.geolocation) { alert('Geolocation not supported'); return; }
      navigator.geolocation.getCurrentPosition(async pos => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        const radius = parseFloat(radiusInput.value) || 5;
        mapDiv.style.display = 'block';
        if (!map) {
          map = L.map('map').setView([lat, lon], 13);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        } else map.setView([lat, lon], 13);
        if (userMarker) userMarker.remove();
        userMarker = L.marker([lat, lon]).addTo(map).bindPopup('You are here').openPopup();

        // fetch nearby listings
        try {
          const res = await fetch(`api_listings.php?lat=${lat}&lon=${lon}&radius=${radius}`);
          const sellers = await res.json();
          showSellersOnMap(sellers, lat, lon);
        } catch (e) {
          alert('Failed to load listings.');
        }
      }, err => {
        alert('Unable to get location.');
      }, {enableHighAccuracy:true, timeout:10000});
    });

    function clearMarkers(){
      sellerMarkers.forEach(m => map.removeLayer(m));
      sellerMarkers = [];
      listDiv.innerHTML = '';
    }

    function showSellersOnMap(sellers, userLat, userLon){
      clearMarkers();
      if (!sellers || sellers.length === 0) {
        listDiv.innerHTML = '<p>No sellers found in radius.</p>';
        return;
      }
      sellers.forEach(s => {
        const m = L.marker([s.lat, s.lon]).addTo(map);
        const popupHtml = `<strong>${escapeHtml(s.title)}</strong><br>${escapeHtml(s.description || '')}<br><em>${s.distance_km.toFixed(2)} km</em><br>
          <button onclick="openChat(${s.id})">Message seller</button>`;
        m.bindPopup(popupHtml);
        sellerMarkers.push(m);

        const item = document.createElement('div');
        item.innerHTML = `<strong>${escapeHtml(s.title)}</strong> — ${escapeHtml(s.description || '')} <br><small>${s.distance_km.toFixed(2)} km — Seller: ${escapeHtml(s.seller_username)}</small>`;
        item.style.borderBottom = '1px solid #eee';
        item.style.padding = '6px';
        item.addEventListener('click', ()=> { map.setView([s.lat, s.lon], 16); m.openPopup(); });
        listDiv.appendChild(item);
      });
    }

    function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    // open chat prompt and send message
    window.openChat = function(listingId){
      const body = prompt('Message to seller about this listing:');
      if (!body) return;
      fetch('send_message.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `listing_id=${encodeURIComponent(listingId)}&body=${encodeURIComponent(body)}`
      }).then(r => r.json()).then(j => {
        if (j && j.ok) alert('Message sent to seller.');
        else alert('Failed to send message.');
      }).catch(()=> alert('Network error.'));
    };
  </script>
</body>
</html>
