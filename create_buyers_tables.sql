-- SQL to create buyers and messaging tables in Chickacc database
-- Stores buyer information, inquiries, and buyer-seller communication

-- Buyers table - stores buyer profile and location information
CREATE TABLE IF NOT EXISTS buyers (
  buyer_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  fullname VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  location_address VARCHAR(500),
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  preferences VARCHAR(1000),
  buyer_agent TEXT,
  consent_text LONGTEXT,
  consent_timestamp DATETIME,
  terms_accepted INT DEFAULT 0,
  accepted_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES credentialss(ids) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_created_at (created_at),
  INDEX idx_location (latitude, longitude)
);

-- Buyer inquiries - tracks which sellers the buyer is interested in
CREATE TABLE IF NOT EXISTS buyer_inquiries (
  inquiry_id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT NOT NULL,
  location_id INT NOT NULL,
  seller_id INT NOT NULL,
  inquiry_status ENUM('active', 'archived', 'contacted') DEFAULT 'active',
  interested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_interaction DATETIME,
  FOREIGN KEY (buyer_id) REFERENCES buyers(buyer_id) ON DELETE CASCADE,
  FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES credentialss(ids) ON DELETE CASCADE,
  INDEX idx_buyer_id (buyer_id),
  INDEX idx_seller_id (seller_id),
  INDEX idx_status (inquiry_status),
  UNIQUE KEY unique_inquiry (buyer_id, location_id, seller_id)
);

-- Messages table - buyer-seller chat messages
CREATE TABLE IF NOT EXISTS buyer_seller_messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  inquiry_id INT NOT NULL,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  sender_type ENUM('buyer', 'seller') NOT NULL,
  message_content LONGTEXT NOT NULL,
  is_read INT DEFAULT 0,
  read_at DATETIME,
  sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (inquiry_id) REFERENCES buyer_inquiries(inquiry_id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES buyers(buyer_id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES credentialss(ids) ON DELETE CASCADE,
  INDEX idx_inquiry_id (inquiry_id),
  INDEX idx_buyer_id (buyer_id),
  INDEX idx_seller_id (seller_id),
  INDEX idx_sent_at (sent_at),
  INDEX idx_unread (is_read, seller_id)
);

-- Buyer seller product view history
CREATE TABLE IF NOT EXISTS buyer_product_views (
  view_id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT NOT NULL,
  location_id INT NOT NULL,
  seller_id INT NOT NULL,
  view_count INT DEFAULT 1,
  last_viewed DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (buyer_id) REFERENCES buyers(buyer_id) ON DELETE CASCADE,
  FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES credentialss(ids) ON DELETE CASCADE,
  UNIQUE KEY unique_view (buyer_id, location_id, seller_id),
  INDEX idx_buyer_id (buyer_id),
  INDEX idx_last_viewed (last_viewed)
);

-- Create indexes for efficient nearby seller queries
CREATE INDEX idx_nearby_sellers ON locations(latitude, longitude);
CREATE INDEX idx_active_locations ON locations(user_id, saved_at);

-- Alter locations table to link with buyers if needed
ALTER TABLE locations ADD COLUMN views_count INT DEFAULT 0;
ALTER TABLE locations ADD COLUMN interested_buyers INT DEFAULT 0;
