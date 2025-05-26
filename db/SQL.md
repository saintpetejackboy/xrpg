# XRPG Database Schema (Summary)

## Core Tables

### classes
- id (PK, int, auto)
- name (varchar, unique)
- description (text)
- tier (int)
- strength_bonus, vitality_bonus, agility_bonus, intelligence_bonus, wisdom_bonus, luck_bonus (int)
- strength_growth, vitality_growth, agility_growth, intelligence_growth, wisdom_growth, luck_growth (decimal)
- special_abilities (json/text)
- lore_text (text)
- is_active (tinyint)
- sort_order (int)
- created_at (timestamp)

### jobs
- id (PK, int, auto)
- name (varchar, unique)
- description (text)
- category (enum: combat, crafting, social, exploration, trade)
- strength_bonus, vitality_bonus, agility_bonus, intelligence_bonus, wisdom_bonus, luck_bonus (int)
- idle_gold_rate (decimal)
- merchant_discount (decimal)
- special_abilities (json/text)
- lore_text (text)
- is_active (tinyint)
- sort_order (int)
- created_at (timestamp)

### races
- id (PK, int, auto)
- name (varchar, unique)
- description (text)
- strength_mod, vitality_mod, agility_mod, intelligence_mod, wisdom_mod, luck_mod (int)
- special_abilities (json/text)
- lore_text (text)
- is_active (tinyint)
- sort_order (int)
- created_at (timestamp)

### prerequisites
- id (PK, int, auto)
- target_type (enum: class, job)
- target_id (int)
- prereq_type (enum: level, race, class, job, class_level, job_level, stat, item, achievement)
- requirement (json/text)
- description (varchar)
- is_active (tinyint)
- created_at (timestamp)

### users
- id (PK, int, auto)
- username (varchar, unique)
- email (varchar)
- fallback_password_hash (varchar)
- registered_at, updated_at (datetime)
- user_id (text, base64)

### user_characters
- id (PK, int, auto)
- user_id (int, FK: users.id, unique)
- race_id (int, FK: races.id, nullable)
- class_id (int, FK: classes.id, nullable)
- job_id (int, FK: jobs.id, nullable)
- character_created_at, class_selected_at, job_selected_at, last_class_change, last_job_change (timestamp)
- is_character_complete (tinyint)
- created_at, updated_at (timestamp)

### user_stats
- id (PK, int, auto)
- user_id (int, FK: users.id)
- level, experience, gold, health, max_health (int)
- strength, vitality, agility, intelligence, wisdom, luck (int)
- class_experience, class_level, job_experience, job_level (int)
- last_idle_update (timestamp)
- idle_gold_rate (decimal)
- created_at, updated_at (timestamp)

## Item/Inventory System

### item_rarity
- id (PK, int, auto)
- code (varchar, unique)
- name (varchar)
- emoji (varchar)
- color_hex (char)
- base_multiplier (decimal)
- max_extra_attrs (tinyint)
- created_at (timestamp)

### attribute_definitions
- id (PK, int, auto)
- code (varchar, unique)
- name (varchar)
- data_type (enum: int, decimal, percent, bool, string, json)
- unit (varchar)
- description (text)
- default_formula (varchar)
- created_at (timestamp)

### equip_slots
- id (PK, int, auto)
- code (varchar, unique)
- name (varchar)
- slot_group (enum: character, house)
- max_per_char (tinyint)

### items
- id (PK, int, auto)
- name (varchar)
- description (text)
- item_type (enum: weapon, armor, consumable, currency, material, house, misc)
- default_slot_id (int, FK: equip_slots.id, nullable)
- is_two_handed (tinyint)
- is_stackable (tinyint)
- max_stack_size (int)
- base_level, max_level (int)
- rarity_id (int, FK: item_rarity.id)
- sell_value, buy_value (int)
- image_file (varchar)
- bind_rule (enum: none, account, character)
- craftable (tinyint)
- power_base, power_per_lvl (int)
- created_at (timestamp)

### item_attribute_templates
- id (PK, int, auto)
- item_id (int, FK: items.id)
- attribute_id (int, FK: attribute_definitions.id)
- min_val, max_val (decimal)
- is_percent (tinyint)
- scaling_rule (varchar)

### item_restrictions
- id (PK, int, auto)
- item_id (int, FK: items.id)
- restrict_type (enum: level, race, class, job, stat, custom)
- rule_json (json)

### item_instances
- id (PK, bigint, auto)
- base_item_id (int, FK: items.id)
- owner_char_id (int, FK: user_characters.id)
- level (int)
- rarity_id (int, FK: item_rarity.id)
- quantity (int)
- power_score (int)
- is_bound (tinyint)
- bound_at (datetime)
- acquired_at (datetime)
- data_json (json)

### item_instance_attributes
- id (PK, bigint, auto)
- instance_id (bigint, FK: item_instances.id)
- attribute_id (int, FK: attribute_definitions.id)
- value_num (decimal)
- value_text (varchar)

### character_equipment
- character_id (int, FK: user_characters.id)
- slot_id (int, FK: equip_slots.id)
- instance_id (bigint, FK: item_instances.id)
- equipped_at (datetime)
- PK: (character_id, slot_id)

### shops
- id (PK, int, auto)
- name (varchar)
- location (varchar)
- open_json (json)

### shop_inventory
- shop_id (int, FK: shops.id)
- item_id (int, FK: items.id)
- stock_qty (int)
- price (int)
- PK: (shop_id, item_id)

## System & Security

### user_passkeys
- id (PK, int, auto)
- user_id (int, FK: users.id)
- credential_id (varchar, unique)
- public_key (varbinary)
- device_name (varchar)
- created_at, last_used (datetime)

### users_backup
- id (PK, int)
- username (varchar)
- passkey_id (text)
- passkey_public_key (blob)
- email (varchar)
- fallback_password_hash (varchar)
- registered_at, updated_at (datetime)
- user_id (text)

### rate_limits
- id (PK, int, auto)
- ip_address (varchar)
- endpoint (varchar)
- username (varchar)
- attempts (int)
- first_attempt, last_attempt (timestamp)
- blocked_until (timestamp)

### auth_log
- id (PK, int, auto)
- user_id (int, FK: users.id)
- username (varchar)
- event_type (enum)
- description (text)
- ip_addr (varchar)
- user_agent (varchar)
- created_at (datetime)
- rate_limited (tinyint)
- user_agent_hash (varchar)
- country_code (varchar)

### creation_limits
- id (PK, int, auto)
- ip_address (varchar)
- accounts_created (int)
- first_creation, last_creation (timestamp)
- daily_count (int)
- last_daily_reset (date)

### security_events
- id (PK, int, auto)
- ip_address (varchar)
- username (varchar)
- event_type (enum)
- description (text)
- metadata (json/text)
- created_at (timestamp)

### user_preferences
- id (PK, int, auto)
- user_id (int, FK: users.id, unique)
- theme_mode (enum)
- accent_color, accent_secondary (varchar)
- border_radius (int)
- shadow_intensity, ui_opacity (decimal)
- font_family (enum)
- created_at, updated_at (timestamp)
- ip_address (varchar)

## Relationships

- user_characters.user_id → users.id (unique, cascade)
- user_characters.race_id → races.id (set null)
- user_characters.class_id → classes.id (set null)
- user_characters.job_id → jobs.id (set null)
- user_passkeys.user_id, user_preferences.user_id, user_stats.user_id → users.id
- auth_log.user_id → users.id
- item_instances.owner_char_id → user_characters.id
- item_instances.base_item_id → items.id
- item_instances.rarity_id → item_rarity.id
- item_instance_attributes.instance_id → item_instances.id
- item_instance_attributes.attribute_id → attribute_definitions.id
- character_equipment.character_id → user_characters.id
- character_equipment.slot_id → equip_slots.id
- character_equipment.instance_id → item_instances.id
- items.default_slot_id → equip_slots.id
- items.rarity_id → item_rarity.id
- item_attribute_templates.item_id → items.id
- item_attribute_templates.attribute_id → attribute_definitions.id
- item_restrictions.item_id → items.id
- shop_inventory.shop_id → shops.id
- shop_inventory.item_id → items.id

**Indexes**: Unique keys on usernames, credential IDs, user preferences, user_characters per user, and other relevant fields.

---