-- Enhanced XRPG Database Schema for Character Creation System
-- This replaces and extends the existing user_stats system

-- Update user_stats table with new stat system
DROP TABLE IF EXISTS user_stats;
CREATE TABLE user_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    gold INT DEFAULT 100,
    health INT DEFAULT 100,
    max_health INT DEFAULT 100,
    
    -- New RPG stats
    strength INT DEFAULT 10,
    vitality INT DEFAULT 10,
    agility INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    wisdom INT DEFAULT 10,
    luck INT DEFAULT 10,
    
    -- Character progression
    class_experience INT DEFAULT 0,
    class_level INT DEFAULT 1,
    job_experience INT DEFAULT 0,
    job_level INT DEFAULT 1,
    
    -- Idle mechanics
    last_idle_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    idle_gold_rate DECIMAL(10,2) DEFAULT 1.00,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_stats (user_id)
);

-- Races table
CREATE TABLE races (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    
    -- Stat modifiers (can be negative)
    strength_mod INT DEFAULT 0,
    vitality_mod INT DEFAULT 0,
    agility_mod INT DEFAULT 0,
    intelligence_mod INT DEFAULT 0,
    wisdom_mod INT DEFAULT 0,
    luck_mod INT DEFAULT 0,
    
    -- Special bonuses
    special_abilities JSON NULL,
    lore_text TEXT,
    
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    tier INT DEFAULT 1, -- 1 = basic, 2 = advanced, etc.
    
    -- Base stat bonuses
    strength_bonus INT DEFAULT 0,
    vitality_bonus INT DEFAULT 0,
    agility_bonus INT DEFAULT 0,
    intelligence_bonus INT DEFAULT 0,
    wisdom_bonus INT DEFAULT 0,
    luck_bonus INT DEFAULT 0,
    
    -- Stat growth per level (multipliers)
    strength_growth DECIMAL(3,2) DEFAULT 1.00,
    vitality_growth DECIMAL(3,2) DEFAULT 1.00,
    agility_growth DECIMAL(3,2) DEFAULT 1.00,
    intelligence_growth DECIMAL(3,2) DEFAULT 1.00,
    wisdom_growth DECIMAL(3,2) DEFAULT 1.00,
    luck_growth DECIMAL(3,2) DEFAULT 1.00,
    
    -- Special abilities and bonuses
    special_abilities JSON NULL,
    lore_text TEXT,
    
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Jobs table
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    category ENUM('combat', 'crafting', 'social', 'exploration', 'trade') DEFAULT 'trade',
    
    -- Small stat bonuses
    strength_bonus INT DEFAULT 0,
    vitality_bonus INT DEFAULT 0,
    agility_bonus INT DEFAULT 0,
    intelligence_bonus INT DEFAULT 0,
    wisdom_bonus INT DEFAULT 0,
    luck_bonus INT DEFAULT 0,
    
    -- Economic bonuses
    idle_gold_rate DECIMAL(10,2) DEFAULT 1.00, -- Multiplier for idle gold
    merchant_discount DECIMAL(5,2) DEFAULT 0.00, -- Percentage discount
    
    -- Special bonuses
    special_abilities JSON NULL,
    lore_text TEXT,
    
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User character information
CREATE TABLE user_characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Character choices
    race_id INT,
    class_id INT,
    job_id INT,
    
    -- Creation and change tracking
    character_created_at TIMESTAMP NULL,
    class_selected_at TIMESTAMP NULL,
    job_selected_at TIMESTAMP NULL,
    last_class_change TIMESTAMP NULL,
    last_job_change TIMESTAMP NULL,
    
    -- Character completion status
    is_character_complete BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_character (user_id)
);

-- Prerequisites system for classes and jobs
CREATE TABLE prerequisites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- What this prerequisite applies to
    target_type ENUM('class', 'job') NOT NULL,
    target_id INT NOT NULL,
    
    -- Prerequisite type and requirements
    prereq_type ENUM('level', 'race', 'class', 'job', 'class_level', 'job_level', 'stat', 'item', 'achievement') NOT NULL,
    
    -- The requirement details (stored as JSON for flexibility)
    requirement JSON NOT NULL,
    
    -- Optional description for display
    description VARCHAR(255),
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_target (target_type, target_id),
    INDEX idx_prereq_type (prereq_type)
);

-- Insert default races
INSERT INTO races (name, description, strength_mod, vitality_mod, agility_mod, intelligence_mod, wisdom_mod, luck_mod, lore_text, sort_order) VALUES
('Human', 'Versatile and adaptable, humans excel in all areas without major weaknesses.', 0, 0, 0, 0, 0, 2, 'The most common race, humans are known for their determination and ability to excel in any profession they choose.', 1),
('Elf', 'Graceful and intelligent, elves have enhanced mental capabilities and agility.', -1, -1, 3, 2, 1, 0, 'Ancient and wise, elves possess natural grace and a deep connection to magic and nature.', 2),
('Dwarf', 'Hardy and strong, dwarves are renowned for their physical prowess and resilience.', 3, 3, -2, 0, 1, -1, 'Stout and determined, dwarves are master craftsmen with an unbreakable will.', 3),
('Orc', 'Powerful and fierce, orcs possess incredible strength but struggle with mental disciplines.', 4, 2, 0, -2, -1, 1, 'Brutal and direct, orcs solve most problems through raw strength and determination.', 4),
('Halfling', 'Small but lucky, halflings excel at avoiding trouble and finding opportunity.', -2, -1, 2, 0, 1, 4, 'Peaceful and jovial, halflings prefer comfortable lives but possess surprising courage when needed.', 5);

-- Insert basic classes
INSERT INTO classes (name, description, tier, strength_bonus, vitality_bonus, agility_bonus, intelligence_bonus, wisdom_bonus, luck_bonus, strength_growth, vitality_growth, agility_growth, intelligence_growth, wisdom_growth, luck_growth, lore_text, sort_order) VALUES
('Fighter', 'Masters of combat who excel in strength and endurance.', 1, 3, 3, 1, 0, 0, 0, 1.5, 1.3, 1.1, 0.8, 0.9, 1.0, 'Warriors who have dedicated their lives to mastering weapons and armor.', 1),
('Mage', 'Wielders of arcane magic with high intelligence and wisdom.', 1, 0, 1, 0, 4, 2, 0, 0.7, 0.9, 0.9, 1.6, 1.4, 1.0, 'Scholars of the arcane arts who bend reality to their will through magic.', 2),
('Rogue', 'Stealthy and agile, rogues excel at precision and luck.', 1, 1, 1, 4, 1, 0, 2, 1.0, 1.0, 1.5, 1.1, 1.0, 1.3, 'Masters of stealth and precision who strike from the shadows.', 3),
('Cleric', 'Divine spellcasters focused on wisdom and supporting others.', 1, 1, 2, 0, 1, 3, 1, 1.0, 1.2, 0.9, 1.2, 1.5, 1.1, 'Holy priests who channel divine power to heal and protect.', 4),
('Ranger', 'Nature-focused warriors with balanced physical and mental attributes.', 1, 2, 2, 2, 1, 2, 1, 1.2, 1.1, 1.3, 1.0, 1.2, 1.1, 'Guardians of the wild who excel at tracking and survival.', 5);

-- Insert advanced classes with prerequisites
INSERT INTO classes (name, description, tier, strength_bonus, vitality_bonus, agility_bonus, intelligence_bonus, wisdom_bonus, luck_bonus, strength_growth, vitality_growth, agility_growth, intelligence_growth, wisdom_growth, luck_growth, lore_text, sort_order) VALUES
('Knight', 'Elite fighters with enhanced combat prowess and leadership.', 2, 5, 4, 1, 1, 2, 1, 1.7, 1.5, 1.1, 1.0, 1.2, 1.1, 'Noble warriors who have proven their valor in battle and earned the right to lead.', 10),
('Archmage', 'Master spellcasters with unparalleled magical knowledge.', 2, 0, 2, 1, 6, 4, 1, 0.7, 1.0, 1.0, 1.8, 1.6, 1.2, 'The pinnacle of magical achievement, these mages have mastered multiple schools of magic.', 11),
('Assassin', 'Elite rogues specializing in stealth and critical strikes.', 2, 2, 2, 6, 2, 1, 4, 1.1, 1.1, 1.7, 1.2, 1.1, 1.5, 'Masters of death who can eliminate any target with precision and stealth.', 12);

-- Insert basic jobs
INSERT INTO jobs (name, description, category, strength_bonus, vitality_bonus, agility_bonus, intelligence_bonus, wisdom_bonus, luck_bonus, idle_gold_rate, merchant_discount, lore_text, sort_order) VALUES
('Merchant', 'Trade goods for profit and better prices.', 'trade', 0, 0, 0, 2, 1, 2, 1.5, 10.0, 'Savvy traders who know the value of everything and how to turn a profit.', 1),
('Blacksmith', 'Craft weapons and armor, gain strength from forge work.', 'crafting', 2, 1, 0, 1, 0, 0, 0.8, 5.0, 'Master craftsmen who forge the weapons and armor that heroes depend on.', 2),
('Scholar', 'Research and study, gaining wisdom and intelligence.', 'social', 0, 0, 0, 3, 2, 0, 0.7, 0.0, 'Learned individuals who seek knowledge and understanding above material wealth.', 3),
('Gambler', 'Risk it all for potentially massive rewards.', 'social', 0, 0, 1, 1, 0, 4, 2.5, 0.0, 'Risk-takers who live by chance and can win or lose fortunes in a single bet.', 4),
('Farmer', 'Steady work providing food, reliable but modest income.', 'trade', 1, 2, 0, 0, 1, 1, 1.2, 0.0, 'Hardworking folk who provide the food that keeps civilization running.', 5),
('Adventurer', 'Explore and seek treasure, balanced risk and reward.', 'exploration', 1, 1, 1, 1, 1, 2, 1.0, 0.0, 'Bold explorers who seek fortune and glory in dangerous places.', 6);

-- Add prerequisites for advanced classes
INSERT INTO prerequisites (target_type, target_id, prereq_type, requirement, description) VALUES
('class', (SELECT id FROM classes WHERE name = 'Knight'), 'class', '{"class_name": "Fighter", "min_level": 10}', 'Must be a Fighter with class level 10+'),
('class', (SELECT id FROM classes WHERE name = 'Knight'), 'level', '{"min_level": 15}', 'Must be character level 15+'),

('class', (SELECT id FROM classes WHERE name = 'Archmage'), 'class', '{"class_name": "Mage", "min_level": 12}', 'Must be a Mage with class level 12+'),
('class', (SELECT id FROM classes WHERE name = 'Archmage'), 'level', '{"min_level": 20}', 'Must be character level 20+'),
('class', (SELECT id FROM classes WHERE name = 'Archmage'), 'stat', '{"stat": "intelligence", "min_value": 25}', 'Must have 25+ Intelligence'),

('class', (SELECT id FROM classes WHERE name = 'Assassin'), 'class', '{"class_name": "Rogue", "min_level": 8}', 'Must be a Rogue with class level 8+'),
('class', (SELECT id FROM classes WHERE name = 'Assassin'), 'level', '{"min_level": 12}', 'Must be character level 12+'),
('class', (SELECT id FROM classes WHERE name = 'Assassin'), 'stat', '{"stat": "agility", "min_value": 20}', 'Must have 20+ Agility');

-- Create indexes for better performance
CREATE INDEX idx_user_characters_completion ON user_characters (is_character_complete);
CREATE INDEX idx_user_characters_user ON user_characters (user_id);
CREATE INDEX idx_races_active ON races (is_active, sort_order);
CREATE INDEX idx_classes_active ON classes (is_active, tier, sort_order);
CREATE INDEX idx_jobs_active ON jobs (is_active, category, sort_order);
