CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    passkey_id VARBINARY(128) NOT NULL,         -- Credential ID (base64url decoded)
    passkey_public_key VARBINARY(512) NOT NULL, -- Raw COSE key (or PEM)
    email VARCHAR(128),
    fallback_password_hash VARCHAR(255),
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Index for username lookups
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
