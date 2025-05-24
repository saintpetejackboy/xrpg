-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 24, 2025 at 02:29 PM
-- Server version: 10.6.22-MariaDB-0ubuntu0.22.04.1-log
-- PHP Version: 8.1.2-1ubuntu2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xrpg`
--

-- --------------------------------------------------------

--
-- Table structure for table `auth_log`
--

CREATE TABLE `auth_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(64) DEFAULT NULL,
  `event_type` enum('login','logout','fail','passkey_register','password_reset','permission_change','other') NOT NULL,
  `description` text DEFAULT NULL,
  `ip_addr` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(64) NOT NULL,
  `passkey_id` varbinary(128) NOT NULL,
  `passkey_public_key` varbinary(512) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `fallback_password_hash` varchar(255) DEFAULT NULL,
  `registered_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes for dumped tables
--

--
-- Indexes for table `auth_log`
--
ALTER TABLE `auth_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_authlog_user_id` (`user_id`),
  ADD KEY `idx_authlog_event_type` (`event_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auth_log`
--
ALTER TABLE `auth_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Enhanced database schema for XRPG with security improvements


-- Rate limiting table to prevent abuse
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    username VARCHAR(50) NULL,
    attempts INT DEFAULT 1,
    first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL,
    INDEX idx_ip_endpoint (ip_address, endpoint),
    INDEX idx_username (username),
    INDEX idx_blocked_until (blocked_until)
);

-- Enhanced auth_log table with more details
ALTER TABLE auth_log 
ADD COLUMN IF NOT EXISTS rate_limited BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS user_agent_hash VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS country_code VARCHAR(2) NULL;

-- User preferences table for theme settings
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme_mode ENUM('dark', 'light') DEFAULT 'dark',
    accent_color VARCHAR(7) DEFAULT '#5299e0',
    accent_secondary VARCHAR(7) DEFAULT '#81aaff',
    border_radius INT DEFAULT 18,
    shadow_intensity DECIMAL(3,2) DEFAULT 0.36,
    ui_opacity DECIMAL(3,2) DEFAULT 0.96,
    font_family ENUM('sans', 'mono', 'game', 'display') DEFAULT 'sans',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_prefs (user_id)
);

-- Account creation limits table
CREATE TABLE IF NOT EXISTS creation_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    accounts_created INT DEFAULT 1,
    first_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    daily_count INT DEFAULT 1,
    last_daily_reset DATE DEFAULT (CURRENT_DATE),
    INDEX idx_ip (ip_address),
    INDEX idx_daily_reset (last_daily_reset)
);

-- Suspicious activity tracking
CREATE TABLE IF NOT EXISTS security_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(50) NULL,
    event_type ENUM('multiple_failures', 'rapid_attempts', 'suspicious_timing', 'blocked_creation') NOT NULL,
    description TEXT,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_type (ip_address, event_type),
    INDEX idx_created_at (created_at)
);

-- Clean up old rate limit entries (run this periodically)
-- DELETE FROM rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR);
-- DELETE FROM security_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);