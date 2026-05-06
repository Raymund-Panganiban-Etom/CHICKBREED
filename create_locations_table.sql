-- SQL to create locations table in Chickacc database
-- This table stores user locations linked to their account by unique ID

CREATE TABLE IF NOT EXISTS locations (
  location_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  description VARCHAR(500),
  socmed VARCHAR(255),
  number VARCHAR(20),
  location_address VARCHAR(500),
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  photo LONGBLOB,
  photo_name VARCHAR(255),
  consent_text LONGTEXT,
  consent_timestamp DATETIME,
  user_agent TEXT,
  device_info VARCHAR(255),
  saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES credentialss(ids) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_saved_at (saved_at)
);

-- Optional: Create an index for faster queries
CREATE INDEX idx_user_location ON locations(user_id, saved_at DESC);
