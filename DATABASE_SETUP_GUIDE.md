# Database-Powered Location Seller System - Setup Guide

## Overview
This system replaces the localStorage implementation with a database-driven approach. It captures device location when users accept the consent form and stores all data in the Chickacc database.

## Files Created/Modified

### 1. **create_locations_table.sql**
Contains the SQL to create the `locations` table in your Chickacc database.

**What it does:**
- Creates a `locations` table with fields for description, contact info, location coordinates, and photos
- Links entries to users via `user_id` (foreign key to `credentialss.ids`)
- Stores latitude/longitude from device geolocation
- Stores photos as binary data (LONGBLOB)
- Includes timestamps for audit trail

**Setup Steps:**
```sql
-- Option 1: Run directly in phpMyAdmin
1. Open phpMyAdmin
2. Select Chickacc database
3. Go to SQL tab
4. Copy & paste contents of create_locations_table.sql
5. Execute

-- Option 2: Run via MySQL command line
mysql -u root -p Chickacc < create_locations_table.sql
```

### 2. **sell_handler.php** (NEW)
Backend PHP file that handles all database operations.

**Endpoints:**
- `?action=getSession` - Retrieves current user_id from session
- `?action=getEntries` - Fetches user's saved locations
- `?action=saveEntry` - Saves new location entry with photo & coordinates
- `?action=deleteEntry` - Removes an entry
- `?action=getPhoto` - Retrieves photo image by location_id

**Key Features:**
- Session management (requires user to be logged in)
- Prepared statements (SQL injection protection)
- Binary photo storage (LONGBLOB)
- Latitude/longitude storage for GPS coordinates

### 3. **sell.php** (MODIFIED)
Frontend updated with:

**Removed:**
- localStorage implementation
- All local storage references

**Added:**
- Database integration via fetch API
- Geolocation API calls on consent acceptance
- Location coordinates capture (latitude/longitude)
- Photo upload as base64 to database
- Dynamic entry loading from database
- User authentication requirement

**New Features:**
- Auto-captures device location after consent
- Displays latitude/longitude coordinates
- Shows location capture status in real-time
- Displays saved timestamps
- Loads entries on page load

## How It Works

### User Flow:
1. User clicks **+** to open form
2. Fills in: description, optional photo, social media, phone, location
3. Clicks **Submit** → Consent modal appears
4. After checking consent checkbox and clicking **Accept**:
   - System requests device location (Geolocation API)
   - Shows location capture status
   - Saves all data to database with GPS coordinates
   - Reloads and displays entry with coordinates

### Database Storage:
```
Entry Storage:
├── user_id (linked to credentialss.ids)
├── description
├── socmed
├── number
├── location_address (manual input or auto-filled from coordinates)
├── latitude & longitude (GPS coordinates)
├── photo (binary data)
├── consent_text (consent record)
├── consent_timestamp
├── device_info (user agent)
├── saved_at (entry creation time)
└── updated_at (modification time)
```

## Authentication Setup

The system expects users to be logged in. You have two options:

**Option A: Session-Based (Recommended)**
```php
// In your login.php, after verifying credentials:
session_start();
$_SESSION['user_id'] = $user_id; // The id from credentialss table
```

**Option B: Manual Entry**
If no session exists, users see a prompt to enter their User ID.

## Security Considerations

✅ **Implemented:**
- Prepared statements (prevents SQL injection)
- Foreign key constraints (data integrity)
- User ownership verification (can only delete own entries)
- Photo size validation (browser-side)

⚠️ **Recommended Additions:**
```php
// Add authentication check at top of sell_handler.php:
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Add CORS headers if needed:
header("Access-Control-Allow-Origin: https://yourdomain.com");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");
```

## Testing

1. **Create Table:**
   - Run the SQL from create_locations_table.sql

2. **Test Backend:**
   - Navigate to: `http://localhost/chickbreed42626248pm/sell_handler.php?action=getSession`
   - Should return: `{"success":false,"user_id":null}` (no session yet)

3. **Test Frontend:**
   - Open sell.php
   - Click +, fill form, submit
   - Check if location is captured (may need browser permission)
   - Verify entry saved in database

4. **Verify Database:**
   ```sql
   SELECT * FROM locations;
   SELECT * FROM locations WHERE user_id = 1;
   ```

## Troubleshooting

### "Authentication required" message
- Solution: Ensure user is logged in or add `$_SESSION['user_id']` to login.php

### Location not capturing
- Check browser permissions for Geolocation
- Firefox/Chrome: Allow location access when prompted
- Some sites require HTTPS for geolocation (localhost OK for testing)

### Photos not displaying
- Verify photo size isn't too large
- Check browser console for fetch errors
- Ensure sell_handler.php has correct database credentials

### Database connection errors
```php
// Debug connection in sell_handler.php
echo mysqli_error($conn); // Add this to see exact error
```

## Database Schema Reference

```sql
CREATE TABLE locations (
  location_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,                          -- Links to credentialss.ids
  description VARCHAR(500),                      -- Item/listing description
  socmed VARCHAR(255),                           -- Social media contact
  number VARCHAR(20),                            -- Phone number
  location_address VARCHAR(500),                 -- Manual location or coordinates
  latitude DECIMAL(10, 8),                       -- GPS latitude
  longitude DECIMAL(11, 8),                      -- GPS longitude
  photo LONGBLOB,                                -- Binary photo data
  photo_name VARCHAR(255),                       -- Original filename
  consent_text LONGTEXT,                         -- Full consent text
  consent_timestamp DATETIME,                    -- When consent given
  user_agent TEXT,                               -- Browser/device info
  device_info VARCHAR(255),                      -- Additional device info
  saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- Entry creation time
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, -- Last modified time
  FOREIGN KEY (user_id) REFERENCES credentialss(ids) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_saved_at (saved_at)
);
```

## Integration with Existing Code

If you have a login system already in place, add this after successful login:

```php
// In your login verification code (e.g., login.php):
if (password_verify($password, $stored_hash)) {
    session_start();
    $_SESSION['user_id'] = $user_id;  // Store the id from credentialss table
    $_SESSION['username'] = $username;
    // ... redirect to dashboard or sell.php
}
```

## Next Steps

1. ✓ Create the locations table in Chickacc database
2. ✓ Ensure users have $_SESSION['user_id'] set on login
3. ✓ Test the form with location capture
4. ✓ Verify entries in database
5. Add email notifications for new listings (optional)
6. Add admin dashboard to view all seller locations (optional)
