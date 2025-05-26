-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 25, 2025 at 09:59 PM
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
CREATE DATABASE IF NOT EXISTS `xrpg` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `xrpg`;

-- --------------------------------------------------------


-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_activity` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `event_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `tier` int(11) DEFAULT 1,
  `strength_bonus` int(11) DEFAULT 0,
  `vitality_bonus` int(11) DEFAULT 0,
  `agility_bonus` int(11) DEFAULT 0,
  `intelligence_bonus` int(11) DEFAULT 0,
  `wisdom_bonus` int(11) DEFAULT 0,
  `luck_bonus` int(11) DEFAULT 0,
  `strength_growth` decimal(3,2) DEFAULT 1.00,
  `vitality_growth` decimal(3,2) DEFAULT 1.00,
  `agility_growth` decimal(3,2) DEFAULT 1.00,
  `intelligence_growth` decimal(3,2) DEFAULT 1.00,
  `wisdom_growth` decimal(3,2) DEFAULT 1.00,
  `luck_growth` decimal(3,2) DEFAULT 1.00,
  `special_abilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`special_abilities`)),
  `lore_text` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('combat','crafting','social','exploration','trade') DEFAULT 'trade',
  `strength_bonus` int(11) DEFAULT 0,
  `vitality_bonus` int(11) DEFAULT 0,
  `agility_bonus` int(11) DEFAULT 0,
  `intelligence_bonus` int(11) DEFAULT 0,
  `wisdom_bonus` int(11) DEFAULT 0,
  `luck_bonus` int(11) DEFAULT 0,
  `idle_gold_rate` decimal(10,2) DEFAULT 1.00,
  `merchant_discount` decimal(5,2) DEFAULT 0.00,
  `special_abilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`special_abilities`)),
  `lore_text` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `prerequisites`
--

CREATE TABLE `prerequisites` (
  `id` int(11) NOT NULL,
  `target_type` enum('class','job') NOT NULL,
  `target_id` int(11) NOT NULL,
  `prereq_type` enum('level','race','class','job','class_level','job_level','stat','item','achievement') NOT NULL,
  `requirement` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`requirement`)),
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `races`
--

CREATE TABLE `races` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `strength_mod` int(11) DEFAULT 0,
  `vitality_mod` int(11) DEFAULT 0,
  `agility_mod` int(11) DEFAULT 0,
  `intelligence_mod` int(11) DEFAULT 0,
  `wisdom_mod` int(11) DEFAULT 0,
  `luck_mod` int(11) DEFAULT 0,
  `special_abilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`special_abilities`)),
  `lore_text` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(64) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `fallback_password_hash` varchar(255) DEFAULT NULL,
  `registered_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` text NOT NULL COMMENT 'Base64 encoded user ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------

--
-- Table structure for table `user_characters`
--

CREATE TABLE `user_characters` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `race_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  `character_created_at` timestamp NULL DEFAULT NULL,
  `class_selected_at` timestamp NULL DEFAULT NULL,
  `job_selected_at` timestamp NULL DEFAULT NULL,
  `last_class_change` timestamp NULL DEFAULT NULL,
  `last_job_change` timestamp NULL DEFAULT NULL,
  `is_character_complete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;





--
-- Table structure for table `user_stats`
--

CREATE TABLE `user_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `level` int(11) DEFAULT 1,
  `experience` int(11) DEFAULT 0,
  `gold` int(11) DEFAULT 100,
  `health` int(11) DEFAULT 100,
  `max_health` int(11) DEFAULT 100,
  `strength` int(11) DEFAULT 10,
  `vitality` int(11) DEFAULT 10,
  `agility` int(11) DEFAULT 10,
  `intelligence` int(11) DEFAULT 10,
  `wisdom` int(11) DEFAULT 10,
  `luck` int(11) DEFAULT 10,
  `class_experience` int(11) DEFAULT 0,
  `class_level` int(11) DEFAULT 1,
  `job_experience` int(11) DEFAULT 0,
  `job_level` int(11) DEFAULT 1,
  `last_idle_update` timestamp NOT NULL DEFAULT current_timestamp(),
  `idle_gold_rate` decimal(10,2) DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/* ===========================================================
   XRPG – ITEM / EQUIPMENT / INVENTORY BACK-END
   -----------------------------------------------------------
   Everything uses utf8mb4 for full-emoji support.
   =========================================================== */

USE `xrpg`;

/*------------------------------------------------------------
  1.  RARITY MASTER
  ------------------------------------------------------------*/
CREATE TABLE `item_rarity` (
  `id`                INT            NOT NULL AUTO_INCREMENT,
  `code`              VARCHAR(32)    NOT NULL UNIQUE,          -- e.g. common, rare, epic
  `name`              VARCHAR(64)    NOT NULL,
  `emoji`             VARCHAR(4)     DEFAULT NULL,             -- 🔹 🔸 ⭐️ etc.
  `color_hex`         CHAR(7)        DEFAULT '#FFFFFF',        -- UI accent (#RRGGBB)
  `base_multiplier`   DECIMAL(6,3)   DEFAULT 1.000,            -- Stat / value scaling
  `max_extra_attrs`   TINYINT UNSIGNED DEFAULT 0,              -- Extra affixes allowed
  `created_at`        TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*------------------------------------------------------------
  2.  GENERIC ATTRIBUTE CATALOG
  ------------------------------------------------------------*/
CREATE TABLE `attribute_definitions` (
  `id`              INT             NOT NULL AUTO_INCREMENT,
  `code`            VARCHAR(32)     NOT NULL UNIQUE,      -- str, vit, fire_resist, gold_rate …
  `name`            VARCHAR(64)     NOT NULL,
  `data_type`       ENUM('int','decimal','percent','bool','string','json')
                                    NOT NULL DEFAULT 'int',
  `unit`            VARCHAR(16)     DEFAULT NULL,         -- %, pts, sec, etc.
  `description`     TEXT            DEFAULT NULL,
  `default_formula` VARCHAR(128)    DEFAULT NULL,         -- optional backend helper
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*------------------------------------------------------------
  3.  EQUIPMENT SLOT DEFINITIONS
  ------------------------------------------------------------*/
CREATE TABLE `equip_slots` (
  `id`           INT           NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(32)   NOT NULL UNIQUE,   -- head, chest, ring, house_slot …
  `name`         VARCHAR(64)   NOT NULL,
  `slot_group`   ENUM('character','house') NOT NULL DEFAULT 'character',
  `max_per_char` TINYINT UNSIGNED DEFAULT 1,      -- rings = 5, house_slot = 10, etc.
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Populate core slots (example) */
INSERT IGNORE INTO `equip_slots` (`code`,`name`,`slot_group`,`max_per_char`) VALUES
 ('head','Head','character',1),
 ('chest','Chest','character',1),
 ('hands','Hands','character',1),
 ('left_hand','Left Hand','character',1),
 ('right_hand','Right Hand','character',1),
 ('legs','Legs','character',1),
 ('boots','Boots','character',1),
 ('ring','Ring','character',5),
 ('necklace','Necklace','character',1),
 ('house_slot','House Item','house',10);

/*------------------------------------------------------------
  4.  ITEM BLUEPRINTS  (what the designers create)
  ------------------------------------------------------------*/
CREATE TABLE `items` (
  `id`               INT            NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(128)   NOT NULL,
  `description`      TEXT           DEFAULT NULL,
  `item_type`        ENUM('weapon','armor','consumable','currency','material',
                          'house','misc')
                                    NOT NULL DEFAULT 'misc',
  `default_slot_id`  INT            DEFAULT NULL,  -- FK equip_slots.id (nullable for potions)
  `is_two_handed`    TINYINT(1)     DEFAULT 0,
  `is_stackable`     TINYINT(1)     DEFAULT 0,
  `max_stack_size`   INT            DEFAULT 99,
  `base_level`       INT            DEFAULT 1,
  `max_level`        INT            DEFAULT 99,
  `rarity_id`        INT            NOT NULL DEFAULT 1, -- FK item_rarity.id
  `sell_value`       INT            DEFAULT 0,
  `buy_value`        INT            DEFAULT 0,
  `image_file`       VARCHAR(128)   DEFAULT 'default.png',
  `bind_rule`        ENUM('none','account','character') DEFAULT 'none',
  `craftable`        TINYINT(1)     DEFAULT 0,
  `power_base`       INT            DEFAULT 0,     -- rough baseline used for scaling
  `power_per_lvl`    INT            DEFAULT 0,
  `created_at`       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `items_rarity_fk` FOREIGN KEY (`rarity_id`) REFERENCES `item_rarity`(`id`),
  CONSTRAINT `items_slot_fk`   FOREIGN KEY (`default_slot_id`) REFERENCES `equip_slots`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*------------------------------------------------------------
  5.  ATTRIBUTE RANGES PER BLUEPRINT
      (min/max used when rolling an instance)
  ------------------------------------------------------------*/
CREATE TABLE `item_attribute_templates` (
  `id`            INT     NOT NULL AUTO_INCREMENT,
  `item_id`       INT     NOT NULL,
  `attribute_id`  INT     NOT NULL,
  `min_val`       DECIMAL(12,4) DEFAULT 0,
  `max_val`       DECIMAL(12,4) DEFAULT 0,
  `is_percent`    TINYINT(1)    DEFAULT 0,
  `scaling_rule`  VARCHAR(64)   DEFAULT NULL,  -- e.g. "linear", "sqrt", custom code key
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_attr_unique` (`item_id`,`attribute_id`),
  CONSTRAINT `tmpl_item_fk`      FOREIGN KEY (`item_id`)      REFERENCES `items`(`id`) ON DELETE CASCADE,
  CONSTRAINT `tmpl_attribute_fk` FOREIGN KEY (`attribute_id`) REFERENCES `attribute_definitions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*------------------------------------------------------------
  6.  OPEN-ENDED ITEM RESTRICTIONS
      (race / class / stat / custom … JSON keeps it future-proof)
  ------------------------------------------------------------*/
CREATE TABLE `item_restrictions` (
  `id`             INT      NOT NULL AUTO_INCREMENT,
  `item_id`        INT      NOT NULL,
  `restrict_type`  ENUM('level','race','class','job','stat','custom') NOT NULL,
  `rule_json`      JSON     NOT NULL,     -- validated on insert
  PRIMARY KEY (`id`),
  CONSTRAINT `restr_item_fk` FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*------------------------------------------------------------
  7.  ITEM INSTANCES  (what actually belongs to players)
  ------------------------------------------------------------*/
CREATE TABLE `item_instances` (
  `id`               BIGINT         NOT NULL AUTO_INCREMENT,
  `base_item_id`     INT            NOT NULL,
  `owner_char_id`    INT            NOT NULL,                -- FK user_characters.id
  `level`            INT            DEFAULT 1,
  `rarity_id`        INT            NOT NULL,
  `quantity`         INT            DEFAULT 1,               -- >1 only if stackable
  `power_score`      INT            DEFAULT 0,               -- quick lookup
  `is_bound`         TINYINT(1)     DEFAULT 0,
  `bound_at`         DATETIME       DEFAULT NULL,
  `acquired_at`      DATETIME       DEFAULT CURRENT_TIMESTAMP,
  `data_json`        JSON           DEFAULT NULL,            -- overflow / future use
  PRIMARY KEY (`id`),
  INDEX  `owner_idx` (`owner_char_id`),
  CONSTRAINT `inst_item_fk`   FOREIGN KEY (`base_item_id`)  REFERENCES `items`(`id`),
  CONSTRAINT `inst_rarity_fk` FOREIGN KEY (`rarity_id`)     REFERENCES `item_rarity`(`id`),
  CONSTRAINT `inst_owner_fk`  FOREIGN KEY (`owner_char_id`) REFERENCES `user_characters`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*------------------------------------------------------------
  8.  ROLLED ATTRIBUTES FOR EACH INSTANCE
  ------------------------------------------------------------*/
CREATE TABLE `item_instance_attributes` (
  `id`            BIGINT  NOT NULL AUTO_INCREMENT,
  `instance_id`   BIGINT  NOT NULL,
  `attribute_id`  INT     NOT NULL,
  `value_num`     DECIMAL(18,4) DEFAULT 0,
  `value_text`    VARCHAR(128)  DEFAULT NULL,  -- for string / JSON types
  PRIMARY KEY (`id`),
  UNIQUE KEY `inst_attr_unique` (`instance_id`,`attribute_id`),
  CONSTRAINT `ia_instance_fk`  FOREIGN KEY (`instance_id`)  REFERENCES `item_instances`(`id`) ON DELETE CASCADE,
  CONSTRAINT `ia_attribute_fk` FOREIGN KEY (`attribute_id`) REFERENCES `attribute_definitions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*------------------------------------------------------------
  9.  EQUIPMENT MAPPER  (character & house use same table)
  ------------------------------------------------------------*/
CREATE TABLE `character_equipment` (
  `character_id`  INT    NOT NULL,
  `slot_id`       INT    NOT NULL,
  `instance_id`   BIGINT NOT NULL,
  `equipped_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`character_id`,`slot_id`),
  CONSTRAINT `eq_char_fk`  FOREIGN KEY (`character_id`) REFERENCES `user_characters`(`id`) ON DELETE CASCADE,
  CONSTRAINT `eq_slot_fk`  FOREIGN KEY (`slot_id`)      REFERENCES `equip_slots`(`id`),
  CONSTRAINT `eq_inst_fk`  FOREIGN KEY (`instance_id`)  REFERENCES `item_instances`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*------------------------------------------------------------
  10. OPTIONAL: SIMPLE SHOP & CRAFTING STUBS (future)
  ------------------------------------------------------------*/
CREATE TABLE `shops` (
  `id`        INT          NOT NULL AUTO_INCREMENT,
  `name`      VARCHAR(64)  NOT NULL,
  `location`  VARCHAR(64)  DEFAULT NULL,
  `open_json` JSON         DEFAULT NULL,   -- schedule, requirements…
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `shop_inventory` (
  `shop_id`     INT     NOT NULL,
  `item_id`     INT     NOT NULL,
  `stock_qty`   INT     DEFAULT NULL,     -- NULL = infinite
  `price`       INT     DEFAULT NULL,     -- NULL = auto (buy_value*markup)
  PRIMARY KEY (`shop_id`,`item_id`),
  CONSTRAINT `si_shop_fk` FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`) ON DELETE CASCADE,
  CONSTRAINT `si_item_fk` FOREIGN KEY (`item_id`) REFERENCES `items`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



----------------------------------------------------------------------------
----------------------------------------------------------------------------
-----------------------------SYSTEM-----------------------------------------------
----------------------------------------------------------------------------


--
-- Table structure for table `user_passkeys`
--

CREATE TABLE `user_passkeys` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `public_key` varbinary(512) NOT NULL,
  `device_name` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `users_backup`
--

CREATE TABLE `users_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `username` varchar(64) NOT NULL,
  `passkey_id` text NOT NULL COMMENT 'Base64url encoded credential ID',
  `passkey_public_key` blob NOT NULL COMMENT 'Binary public key data',
  `email` varchar(128) DEFAULT NULL,
  `fallback_password_hash` varchar(255) DEFAULT NULL,
  `registered_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` text NOT NULL COMMENT 'Base64 encoded user ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `attempts` int(11) DEFAULT 1,
  `first_attempt` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `blocked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
  `created_at` datetime DEFAULT current_timestamp(),
  `rate_limited` tinyint(1) DEFAULT 0,
  `user_agent_hash` varchar(64) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



--
-- Table structure for table `creation_limits`
--

CREATE TABLE `creation_limits` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `accounts_created` int(11) DEFAULT 1,
  `first_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_creation` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `daily_count` int(11) DEFAULT 1,
  `last_daily_reset` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




--
-- Table structure for table `security_events`
--

CREATE TABLE `security_events` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `event_type` enum('multiple_failures','rapid_attempts','suspicious_timing','blocked_creation') NOT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------


--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `theme_mode` enum('dark','light') DEFAULT 'dark',
  `accent_color` varchar(7) DEFAULT '#5299e0',
  `accent_secondary` varchar(7) DEFAULT '#81aaff',
  `border_radius` int(11) DEFAULT 18,
  `shadow_intensity` decimal(3,2) DEFAULT 0.36,
  `ui_opacity` decimal(3,2) DEFAULT 0.96,
  `font_family` enum('sans','mono','game','display') DEFAULT 'sans',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
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
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_classes_active` (`is_active`,`tier`,`sort_order`);

--
-- Indexes for table `creation_limits`
--
ALTER TABLE `creation_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_daily_reset` (`last_daily_reset`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_jobs_active` (`is_active`,`category`,`sort_order`);

--
-- Indexes for table `prerequisites`
--
ALTER TABLE `prerequisites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_prereq_type` (`prereq_type`);

--
-- Indexes for table `races`
--
ALTER TABLE `races`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_races_active` (`is_active`,`sort_order`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_endpoint` (`ip_address`,`endpoint`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_blocked_until` (`blocked_until`);

--
-- Indexes for table `security_events`
--
ALTER TABLE `security_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_type` (`ip_address`,`event_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`);

--
-- Indexes for table `user_characters`
--
ALTER TABLE `user_characters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_character` (`user_id`),
  ADD KEY `race_id` (`race_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `idx_user_characters_completion` (`is_character_complete`),
  ADD KEY `idx_user_characters_user` (`user_id`);

--
-- Indexes for table `user_passkeys`
--
ALTER TABLE `user_passkeys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_credential_id` (`credential_id`),
  ADD KEY `idx_user_passkeys_user_id` (`user_id`),
  ADD KEY `idx_user_passkeys_credential_id` (`credential_id`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_prefs` (`user_id`);

--
-- Indexes for table `user_stats`
--
ALTER TABLE `user_stats`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auth_log`
--
ALTER TABLE `auth_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `creation_limits`
--
ALTER TABLE `creation_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `prerequisites`
--
ALTER TABLE `prerequisites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `races`
--
ALTER TABLE `races`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `security_events`
--
ALTER TABLE `security_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_characters`
--
ALTER TABLE `user_characters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_passkeys`
--
ALTER TABLE `user_passkeys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_stats`
--
ALTER TABLE `user_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_characters`
--
ALTER TABLE `user_characters`
  ADD CONSTRAINT `user_characters_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_characters_ibfk_2` FOREIGN KEY (`race_id`) REFERENCES `races` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_characters_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_characters_ibfk_4` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
