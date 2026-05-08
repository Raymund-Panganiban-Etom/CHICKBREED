-- Create tables to support buyer inquiries, messages and seller notifications

CREATE TABLE IF NOT EXISTS buyer_inquiries (
  inquiry_id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT NOT NULL,
  location_id INT NOT NULL,
  seller_id INT NOT NULL,
  inquiry_status ENUM('active','archived','contacted') DEFAULT 'active',
  interested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_interaction DATETIME,
  UNIQUE KEY uq_inquiry (buyer_id, location_id, seller_id),
  INDEX idx_buyer_id (buyer_id),
  INDEX idx_seller_id (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS buyer_seller_messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  inquiry_id INT NOT NULL,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  sender_type ENUM('buyer','seller') NOT NULL,
  message_content LONGTEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  read_at DATETIME,
  sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inquiry_id (inquiry_id),
  INDEX idx_buyer_id (buyer_id),
  INDEX idx_seller_id (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS seller_notifications (
  notif_id INT AUTO_INCREMENT PRIMARY KEY,
  seller_id INT NOT NULL,
  buyer_id INT NOT NULL,
  inquiry_id INT NOT NULL,
  message_summary VARCHAR(255),
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_seller_id (seller_id),
  INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: increase interested_buyers column on locations if missing
ALTER TABLE locations 
  ADD COLUMN IF NOT EXISTS interested_buyers INT DEFAULT 0;

-- Note: buyers table and locations table should already exist in Chickacc per earlier setup.
