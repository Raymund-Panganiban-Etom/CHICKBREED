# Map Visualization Feature - Documentation

## Overview
The sell.php system now includes an interactive map visualization using **Leaflet** and **OpenStreetMap** (completely free, no API key required). The map displays your captured device location in real-time during the consent process and allows viewing of saved location entries.

## Features

### 1. **Live Location Capture Map**
- Displays an interactive map in the consent modal after location is captured
- Shows real-time GPS coordinates (latitude/longitude)
- Displays accuracy information
- User can toggle map visibility while reviewing consent

### 2. **Saved Location Maps**
- Each saved entry has a "View Map" button (if location was captured)
- Clicking opens a modal with full-screen map of where the entry was created
- Shows exact GPS coordinates and location title
- Can be closed and reopened multiple times

## How It Works

### During Entry Creation:

1. User clicks **+** and fills form
2. Clicks **Submit** → Consent modal appears
3. User checks consent box and clicks **Accept**
4. System requests device geolocation
5. **Map appears** showing:
   - Interactive map centered on captured location
   - Blue marker with exact coordinates
   - Accuracy (in meters)
   - Show/Hide toggle button
6. User can review location on map before confirming
7. Clicks **Accept** again to save entry with location data

### Viewing Saved Entries:

1. Each saved entry displays:
   - Location address
   - GPS coordinates (latitude, longitude)
   - **"View Map"** button
2. Click **"View Map"** to open interactive map modal
3. Map shows:
   - Centered on captured location
   - Zoom level 16 (good detail)
   - Blue marker with entry title
   - Full coordinate display
   - © OpenStreetMap attribution

## Technology Stack

### Libraries Used:
- **Leaflet.js** v1.9.4 - Open-source mapping library
- **OpenStreetMap** - Free map tiles (no API key needed)
- **CDN Delivery** - Via cdnjs.cloudflare.com

### Why These?
✅ Completely free (no API key needed)
✅ No usage limits
✅ Works offline with cached tiles
✅ Lightweight (~40KB)
✅ Privacy-friendly (data not sent to Google/proprietary services)
✅ Open-source and well-maintained

## Technical Details

### CSS Styling
```css
.map-container: 400px height, hidden by default
.map-info: Shows captured coordinates
.map-toggle: Button to show/hide map
```

### JavaScript Functions

#### `requestDeviceLocation()`
- Requests geolocation using Geolocation API
- Captures latitude, longitude, accuracy
- Initializes map with coordinates
- Updates coordinate display
- Shows map and info sections

#### `initializeMap(lat, lng)`
- Creates Leaflet map instance
- Adds OpenStreetMap tile layer
- Places blue marker at location
- Shows popup with coordinates
- Handles map cleanup

#### `showLocationMap(lat, lng, title)`
- Creates modal popup for viewing saved locations
- Initializes separate map in modal
- Shows coordinates and entry title
- Closeable via X button

#### `toggleMapVisibility()`
- Hides/shows map container during consent
- Refreshes map size when shown
- Updates button text

## Map Features

### Interactivity
- **Drag**: Click and drag to pan the map
- **Zoom**: Use mouse wheel or +/- buttons
- **Tap**: On mobile, pinch to zoom
- **Click Marker**: Opens popup with coordinates

### Map Controls
- Zoom buttons (top-left)
- Attribution (bottom-right shows OpenStreetMap)
- Layer selector (if configured)

### Marker Information
```
Marker shows:
- Location title/description
- Latitude (6 decimal places = ~0.1 meters accuracy)
- Longitude (6 decimal places = ~0.1 meters accuracy)
- Captured accuracy (in meters)
```

## Geolocation API Details

### How It Works
1. Browser requests permission from user
2. User grants or denies access
3. If granted: Captures GPS coordinates + accuracy
4. If denied: Shows error, allows manual entry

### Browser Compatibility
| Browser | Support | Notes |
|---------|---------|-------|
| Chrome/Edge | ✓ | Works on HTTP/HTTPS |
| Firefox | ✓ | Works on HTTP/HTTPS |
| Safari | ✓ | Requires user permission |
| Opera | ✓ | Works on HTTP/HTTPS |
| IE | ✗ | Not supported |

### Mobile Requirements
- **iOS**: App must request location permission in Info.plist
- **Android**: App/browser must request location permission
- **Localhost**: Works without HTTPS for testing
- **Production**: Requires HTTPS for Geolocation API

### Accuracy
- Depends on device GPS hardware
- Urban areas: 5-10 meters
- Rural areas: 10-50 meters
- Indoors: May not work or low accuracy

## Data Storage

### Database Fields
```sql
latitude DECIMAL(10, 8)    -- Stored with 8 decimal places
longitude DECIMAL(11, 8)   -- Stored with 8 decimal places
location_address VARCHAR(500) -- Manual address or auto-filled coordinates
saved_at DATETIME           -- Entry creation timestamp
```

### Precision
- **Decimal(10,8)** = latitude range ±90°, precision to ~0.1 meters
- **Decimal(11,8)** = longitude range ±180°, precision to ~0.1 meters
- 6 decimal places shown in UI = ~0.1 meter accuracy

## Security Considerations

✅ **Location Privacy**
- Geolocation is user-initiated (user clicks Accept)
- Data stored in secure database (not exposed to third parties)
- OpenStreetMap tiles don't track user location
- No data sent to Google or proprietary services

✅ **Map Display**
- Maps only accessible to logged-in user viewing own entries
- No public map sharing by default
- Can add authentication to map viewing if needed

⚠️ **Recommendations**
- Use HTTPS in production
- Implement rate limiting on location requests
- Add user consent documentation (already included)
- Consider adding data retention policy

## Performance

### Load Times
- Leaflet JS: ~40KB (CDN cached)
- OpenStreetMap tiles: Downloaded on demand
- First map load: 1-2 seconds (depends on connection)
- Subsequent maps: Faster (tiles cached by browser)

### Optimization
- Maps only loaded when user interacts with them
- Old maps destroyed before creating new ones
- CDN delivery for fast loading
- Lazy initialization (map created only when needed)

## Troubleshooting

### Map not showing
**Check:**
- Browser console for JavaScript errors
- Leaflet library loaded (check Network tab)
- OpenStreetMap tiles loading (should see map grid)
- Container has height (400px set in CSS)

**Solution:**
```javascript
// Add to browser console to debug:
console.log(L); // Should show Leaflet object
console.log(map); // Should show map instance
map.invalidateSize(); // Force map redraw
```

### Geolocation not working
**Check:**
- Browser location permission granted
- Using HTTPS in production (localhost OK for testing)
- Device GPS enabled
- Not inside building (indoor positioning limited)

**Solution:**
```javascript
// Test geolocation in console:
navigator.geolocation.getCurrentPosition(
  pos => console.log(pos.coords),
  err => console.log(err)
);
```

### Map tiles not loading
**Check:**
- Internet connection
- OpenStreetMap server status (usually up)
- No browser extension blocking maps

**Solution:**
```javascript
// Try alternative tile provider if needed:
L.tileLayer('https://tile.opentopomap.org/{z}/{x}/{y}.png').addTo(map);
```

### Mobile map too small
**Check:**
- Viewport meta tag set (✓ already done)
- Touch gestures enabled (✓ default in Leaflet)
- Zoom buttons visible on mobile (✓ automatic)

**Solution:**
- Adjust `.map-container` height for mobile in CSS

## Future Enhancements

### Potential Features
1. **Route Map**: Show delivery route between multiple locations
2. **Clustering**: Show multiple markers grouped on map
3. **Geocoding**: Convert addresses to coordinates
4. **Distance Calculator**: Show distance between entries
5. **Public Map**: Share location map with buyers
6. **Heatmap**: Show seller density in areas
7. **Directions**: Link to Google Maps/Apple Maps for navigation

### Alternative Providers
If you want to switch map providers:
```javascript
// Google Maps (requires API key):
L.tileLayer('https://tile.opentopomap.org/{z}/{x}/{y}.png')

// Mapbox (requires token):
L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/{lon},{lat},{z}/{w}x{h}@2x?access_token=YOUR_TOKEN')

// Stadia Maps (free tier available):
L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_toner_lite/{z}/{x}/{y}{r}.png')
```

## FAQ

**Q: Is my location data secure?**
A: Yes. Location is stored in your database, not sent to third parties. OpenStreetMap tile loading doesn't expose user location.

**Q: Can I disable map features?**
A: Yes, remove the Leaflet links and comment out map functions if not needed.

**Q: Does map work offline?**
A: Partially. Map tiles need internet to load initially, but browser caching helps on subsequent loads.

**Q: Can I display all sellers on one map?**
A: Yes, this can be implemented as a future feature using map clustering.

**Q: What accuracy should I expect?**
A: Typically 5-50 meters depending on device, location, and environmental factors.

**Q: Does it work on all devices?**
A: Yes, works on desktop browsers and mobile (iOS/Android) that support Geolocation API.

---

**Last Updated:** May 2026
**Map Provider:** OpenStreetMap (Open Data Commons)
**Library Version:** Leaflet 1.9.4
