-- Fix missing tables and optional columns reported in buy_errors.log

-- 1) Create buyer_consent_logs if missing
CREATE TABLE IF NOT EXISTS buyer_consent_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT NOT NULL,
  consent_text TEXT,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  given_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_buyer_id (buyer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) (Optional) Add Email column to credentialss if your app expects it
-- WARNING: only run if you want to store seller emails in credentialss
ALTER TABLE credentialss
  ADD COLUMN IF NOT EXISTS Email VARCHAR(255) DEFAULT NULL;

-- 3) Ensure buyers.user_id references an existing user in credentialss.ids
-- If you see foreign key issues, verify credentialss.ids values or remove FK temporarily.

-- After running these, restart Apache/PHP to clear any cached errors.
