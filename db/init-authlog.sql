CREATE TABLE IF NOT EXISTS auth_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(64),
    event_type ENUM('login','logout','fail','passkey_register','password_reset','permission_change','other') NOT NULL,
    description TEXT,
    ip_addr VARCHAR(45),
    user_agent VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_authlog_user_id ON auth_log(user_id);
CREATE INDEX IF NOT EXISTS idx_authlog_event_type ON auth_log(event_type);
