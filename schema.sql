-- Prime Property Buys — MySQL / MariaDB schema for Hostinger.
-- Create an empty database, then import this file in phpMyAdmin.
-- The app also runs these statements on first use when the database user can create tables.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'owner',
  created_at DATETIME NOT NULL,
  last_login DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190) NOT NULL,
  best_time VARCHAR(40) NOT NULL DEFAULT '',
  tcpa_consent TINYINT(1) NOT NULL DEFAULT 0,
  tcpa_text TEXT NOT NULL,
  tcpa_at DATETIME NULL,
  tcpa_ip VARCHAR(45) NOT NULL DEFAULT '',
  address VARCHAR(300) NOT NULL,
  city VARCHAR(80) NOT NULL DEFAULT '',
  zip VARCHAR(12) NOT NULL DEFAULT '',
  county VARCHAR(80) NOT NULL DEFAULT 'Lowndes',
  property_type VARCHAR(80) NOT NULL DEFAULT '',
  beds VARCHAR(20) NOT NULL DEFAULT '',
  baths VARCHAR(20) NOT NULL DEFAULT '',
  condition_label VARCHAR(80) NOT NULL DEFAULT '',
  occupancy VARCHAR(40) NOT NULL DEFAULT '',
  timeline VARCHAR(80) NOT NULL DEFAULT '',
  reason VARCHAR(120) NOT NULL DEFAULT '',
  asking_price INT UNSIGNED NULL,
  utm VARCHAR(80) NOT NULL DEFAULT '',
  source_path VARCHAR(120) NOT NULL DEFAULT '',
  ip_address VARCHAR(45) NOT NULL DEFAULT '',
  user_agent VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_leads_city_created (city, created_at),
  KEY idx_leads_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deals (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NOT NULL,
  stage VARCHAR(40) NOT NULL DEFAULT 'lead',
  stage_changed_at DATETIME NOT NULL,
  dead_reason VARCHAR(255) NOT NULL DEFAULT '',
  arv INT UNSIGNED NULL,
  repair_est INT UNSIGNED NULL,
  mao INT UNSIGNED NULL,
  offer_amt INT UNSIGNED NULL,
  contract_price INT UNSIGNED NULL,
  contract_date DATE NULL,
  inspection_deadline DATE NULL,
  closing_date DATE NULL,
  title_company VARCHAR(200) NOT NULL DEFAULT '',
  assignment_fee INT UNSIGNED NULL,
  access_notes TEXT NOT NULL,
  last_seller_notice VARCHAR(40) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_deals_lead (lead_id),
  KEY idx_deals_stage (stage, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deal_stage_history (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  deal_id INT UNSIGNED NOT NULL,
  from_stage VARCHAR(40) NOT NULL DEFAULT '',
  to_stage VARCHAR(40) NOT NULL,
  changed_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_stage_deal (deal_id, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type VARCHAR(20) NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_notes_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tasks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type VARCHAR(20) NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  due_at DATE NULL,
  done_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_tasks_due (done_at, due_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NULL,
  buyer_id INT UNSIGNED NULL,
  kind VARCHAR(20) NOT NULL DEFAULT 'photo',
  stored_name VARCHAR(80) NOT NULL,
  original_name VARCHAR(200) NOT NULL,
  mime VARCHAR(80) NOT NULL,
  bytes INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_media_lead (lead_id),
  KEY idx_media_buyer (buyer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buyers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company VARCHAR(200) NOT NULL,
  buyer_type VARCHAR(80) NOT NULL DEFAULT '',
  tier CHAR(1) NOT NULL DEFAULT 'C',
  contact_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  pof_on_file TINYINT(1) NOT NULL DEFAULT 0,
  pof_date DATE NULL,
  closings_count INT UNSIGNED NOT NULL DEFAULT 0,
  email_opt_in TINYINT(1) NOT NULL DEFAULT 0,
  unsubscribed_at DATETIME NULL,
  notes TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_buyers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buy_boxes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  buyer_id INT UNSIGNED NOT NULL,
  areas VARCHAR(500) NOT NULL DEFAULT '',
  property_types VARCHAR(255) NOT NULL DEFAULT '',
  min_beds INT UNSIGNED NULL,
  min_baths INT UNSIGNED NULL,
  price_min INT UNSIGNED NULL,
  price_max INT UNSIGNED NULL,
  max_rehab INT UNSIGNED NULL,
  min_year INT UNSIGNED NULL,
  occupancy_ok VARCHAR(20) NOT NULL DEFAULT 'any',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_boxes_buyer (buyer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deal_sheets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  deal_id INT UNSIGNED NOT NULL,
  token CHAR(32) NOT NULL,
  expires_at DATETIME NOT NULL,
  location_label VARCHAR(200) NOT NULL,
  price_text VARCHAR(80) NOT NULL DEFAULT '',
  arv_text VARCHAR(80) NOT NULL DEFAULT '',
  rehab_text VARCHAR(80) NOT NULL DEFAULT '',
  access_text TEXT NOT NULL,
  attorney_text VARCHAR(200) NOT NULL DEFAULT '',
  deadline_text VARCHAR(120) NOT NULL DEFAULT '',
  show_photos TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sheet_token (token),
  KEY idx_sheet_deal (deal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blasts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  deal_sheet_id INT UNSIGNED NOT NULL,
  buyer_id INT UNSIGNED NOT NULL,
  sent_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_blasts_sheet (deal_sheet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  tcpa_consent TINYINT(1) NOT NULL DEFAULT 0,
  tcpa_text TEXT NOT NULL,
  tcpa_at DATETIME NULL,
  tcpa_ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS form_events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  step VARCHAR(40) NOT NULL,
  session_hash CHAR(32) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_form_events_step (step, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_address VARCHAR(45) NOT NULL,
  bucket VARCHAR(40) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_rate_lookup (ip_address, bucket, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  action VARCHAR(80) NOT NULL,
  entity VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
