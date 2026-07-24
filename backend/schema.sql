-- ============================================================
-- SHE INSPECTION SYSTEM - Database Schema (Updated)
-- Based on EPATROL Database Analysis & Restructuring
-- ============================================================

CREATE DATABASE IF NOT EXISTS she_inspection
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE she_inspection;

-- ============================================================
-- 1. ORGANIZATION STRUCTURE
-- ============================================================

CREATE TABLE companies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address TEXT,
  province VARCHAR(255),
  city VARCHAR(255),
  ceo_name VARCHAR(255),
  ceo_email VARCHAR(255),
  ceo_phone VARCHAR(255),
  group_code VARCHAR(200),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE branches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  address TEXT,
  province VARCHAR(255),
  city VARCHAR(255),
  head_name VARCHAR(255),
  email VARCHAR(255),
  group_code VARCHAR(200),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_branches_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE divisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  head_name VARCHAR(255),
  email VARCHAR(255),
  phone VARCHAR(255),
  group_code VARCHAR(200),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_divisions_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_divisions_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE departments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  branch_id BIGINT UNSIGNED NOT NULL,
  division_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  head_name VARCHAR(100),
  email VARCHAR(100),
  phone VARCHAR(20),
  group_code VARCHAR(200),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_departments_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
  CONSTRAINT fk_departments_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  branch_id BIGINT UNSIGNED NOT NULL,
  division_id BIGINT UNSIGNED NOT NULL,
  department_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  head_name VARCHAR(100),
  email VARCHAR(100),
  phone VARCHAR(20),
  group_code VARCHAR(200),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sections_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
  CONSTRAINT fk_sections_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
  CONSTRAINT fk_sections_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 2. USERS & AUTHENTICATION
-- ============================================================

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(255),
  position VARCHAR(255),
  company_id BIGINT UNSIGNED,
  branch_id BIGINT UNSIGNED,
  division_id BIGINT UNSIGNED,
  department_id BIGINT UNSIGNED,
  section_id BIGINT UNSIGNED,
  role ENUM('super_admin', 'admin', 'inspector', 'viewer') NOT NULL DEFAULT 'inspector',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE user_groups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE user_group_members (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  group_id BIGINT UNSIGNED NOT NULL,
  CONSTRAINT fk_ugm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ugm_group FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE,
  UNIQUE KEY uk_user_group (user_id, group_id)
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id BIGINT UNSIGNED NOT NULL,
  module VARCHAR(255) NOT NULL,
  can_view TINYINT(1) NOT NULL DEFAULT 0,
  can_create TINYINT(1) NOT NULL DEFAULT 0,
  can_edit TINYINT(1) NOT NULL DEFAULT 0,
  can_delete TINYINT(1) NOT NULL DEFAULT 0,
  can_approve TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_permissions_group FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE api_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_api_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_api_tokens_expires (expires_at)
) ENGINE=InnoDB;

-- ============================================================
-- 3. LOCATIONS & ASSETS
-- ============================================================

CREATE TABLE location_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  group_code VARCHAR(200),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE locations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  location_type_id BIGINT UNSIGNED,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  address TEXT,
  company_id BIGINT UNSIGNED,
  branch_id BIGINT UNSIGNED,
  group_code VARCHAR(200),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_locations_type FOREIGN KEY (location_type_id) REFERENCES location_types(id) ON DELETE SET NULL,
  CONSTRAINT fk_locations_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
  CONSTRAINT fk_locations_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE areas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  location_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  group_code VARCHAR(200),
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_areas_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 4. QR CODES & ASSET TRACKING
-- ============================================================

CREATE TABLE asset_qr_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  qr_code VARCHAR(64) NOT NULL UNIQUE,
  asset_type ENUM('fire_hydrant', 'fire_extinguisher', 'fire_alarm', 'es_ew', 'location', 'area') NOT NULL,
  asset_id BIGINT UNSIGNED NOT NULL,
  asset_name VARCHAR(200),
  location_id BIGINT UNSIGNED,
  area_id BIGINT UNSIGNED,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_qr_asset (asset_type, asset_id),
  CONSTRAINT fk_qr_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
  CONSTRAINT fk_qr_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 5. FIRE HYDRANT INSPECTION
-- ============================================================

CREATE TABLE fire_hydrant_inspections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(40) NOT NULL UNIQUE,
  location_id BIGINT UNSIGNED,
  area_id BIGINT UNSIGNED,
  qr_code_id BIGINT UNSIGNED,
  inspection_date DATE NOT NULL,
  inspector_id BIGINT UNSIGNED NOT NULL,
  assigned_to BIGINT UNSIGNED,
  checkin_lat DECIMAL(10,8),
  checkin_lng DECIMAL(11,8),
  checked_in_at DATETIME,
  signed_at DATETIME,
  notes TEXT,
  status ENUM('draft', 'completed', 'signed') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_fhi_inspector FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_fhi_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_fhi_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
  CONSTRAINT fk_fhi_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
  CONSTRAINT fk_fhi_qr FOREIGN KEY (qr_code_id) REFERENCES asset_qr_codes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE fire_hydrant_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  hydrant_number VARCHAR(200) NOT NULL,
  name VARCHAR(100) NOT NULL,
  location_detail VARCHAR(200),
  hose_condition TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=NoGood, 1=Good',
  nozzle_condition TINYINT(1) NOT NULL DEFAULT 0,
  coupling_condition TINYINT(1) NOT NULL DEFAULT 0,
  wrench_condition TINYINT(1) NOT NULL DEFAULT 0,
  valve_condition TINYINT(1) NOT NULL DEFAULT 0,
  coupling_extra_condition TINYINT(1) NOT NULL DEFAULT 0,
  remark TEXT,
  photo_before VARCHAR(200),
  photo_after VARCHAR(200),
  item_lat DECIMAL(10,8),
  item_lng DECIMAL(11,8),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fhi_items_inspection FOREIGN KEY (inspection_id) REFERENCES fire_hydrant_inspections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 6. FIRE EXTINGUISHER INSPECTION
-- ============================================================

CREATE TABLE fire_extinguisher_inspections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(40) NOT NULL UNIQUE,
  location_id BIGINT UNSIGNED NOT NULL,
  inspection_date DATE NOT NULL,
  inspector_id BIGINT UNSIGNED NOT NULL,
  checkin_lat DECIMAL(10,8),
  checkin_lng DECIMAL(11,8),
  checked_in_at DATETIME,
  signed_at DATETIME,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_fei_inspector FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_fei_location FOREIGN KEY (location_id) REFERENCES fire_extinguisher_location(id_location) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE fire_extinguisher_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  type VARCHAR(200),
  location_detail VARCHAR(200),
  pressure_condition TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=NoGood, 1=Good',
  seal_condition TINYINT(1) NOT NULL DEFAULT 0,
  nozzle_condition TINYINT(1) NOT NULL DEFAULT 0,
  remark TEXT,
  expiry_date DATE,
  photo_before VARCHAR(200),
  photo_after VARCHAR(200),
  item_lat DECIMAL(10,8),
  item_lng DECIMAL(11,8),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fei_items_inspection FOREIGN KEY (inspection_id) REFERENCES fire_extinguisher_inspections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. FIRE ALARM INSPECTION
-- ============================================================

CREATE TABLE fire_alarm_inspections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(40) NOT NULL UNIQUE,
  location_id BIGINT UNSIGNED,
  area_id BIGINT UNSIGNED,
  qr_code_id BIGINT UNSIGNED,
  inspection_date DATE NOT NULL,
  inspector_id BIGINT UNSIGNED NOT NULL,
  assigned_to BIGINT UNSIGNED,
  checkin_lat DECIMAL(10,8),
  checkin_lng DECIMAL(11,8),
  checked_in_at DATETIME,
  signed_at DATETIME,
  notes TEXT,
  status ENUM('draft', 'completed', 'signed') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_fai_inspector FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_fai_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_fai_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
  CONSTRAINT fk_fai_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
  CONSTRAINT fk_fai_qr FOREIGN KEY (qr_code_id) REFERENCES asset_qr_codes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE fire_alarm_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  alarm_number VARCHAR(200),
  type VARCHAR(200),
  location_detail VARCHAR(299),
  condition_good TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=NoGood, 1=Good',
  correction_needed TINYINT(1) NOT NULL DEFAULT 0,
  remark TEXT,
  photo_before VARCHAR(200),
  photo_after VARCHAR(200),
  item_lat DECIMAL(10,8),
  item_lng DECIMAL(11,8),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fai_items_inspection FOREIGN KEY (inspection_id) REFERENCES fire_alarm_inspections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 8. EMERGENCY SHOWER / EYE WASH INSPECTION
-- ============================================================

CREATE TABLE es_ew_inspections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(40) NOT NULL UNIQUE,
  location_id BIGINT UNSIGNED,
  area_id BIGINT UNSIGNED,
  qr_code_id BIGINT UNSIGNED,
  inspection_date DATE NOT NULL,
  inspector_id BIGINT UNSIGNED NOT NULL,
  assigned_to BIGINT UNSIGNED,
  checkin_lat DECIMAL(10,8),
  checkin_lng DECIMAL(11,8),
  checked_in_at DATETIME,
  signed_at DATETIME,
  notes TEXT,
  status ENUM('draft', 'completed', 'signed') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_esei_inspector FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_esei_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_esei_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
  CONSTRAINT fk_esei_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
  CONSTRAINT fk_esei_qr FOREIGN KEY (qr_code_id) REFERENCES asset_qr_codes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE es_ew_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  type VARCHAR(200),
  location_detail VARCHAR(200),
  area_detail VARCHAR(200),
  water_flow_es TINYINT(1) COMMENT 'Emergency Shower water flow',
  water_flow_ew TINYINT(1) COMMENT 'Eye Wash water flow',
  water_condition TINYINT(1),
  actual_valve_es TINYINT(1),
  actual_valve_ew TINYINT(1),
  physical_condition_es TINYINT(1),
  physical_condition_ew TINYINT(1),
  sign_board_condition TINYINT(1),
  housekeeping_condition TINYINT(1),
  road_access_condition TINYINT(1),
  sewer_condition TINYINT(1),
  remark TEXT,
  photo_before VARCHAR(200),
  photo_after VARCHAR(200),
  item_lat DECIMAL(10,8),
  item_lng DECIMAL(11,8),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_esei_items_inspection FOREIGN KEY (inspection_id) REFERENCES es_ew_inspections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 9. GENERAL INSPECTION / CHECKLIST
-- ============================================================

CREATE TABLE inspection_checklists (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(40) NOT NULL UNIQUE,
  location_id BIGINT UNSIGNED,
  area_id BIGINT UNSIGNED,
  category_id BIGINT UNSIGNED,
  department_id BIGINT UNSIGNED,
  qr_code_id BIGINT UNSIGNED,
  inspection_date DATE NOT NULL,
  inspector_id BIGINT UNSIGNED NOT NULL,
  checkin_lat DECIMAL(10,8),
  checkin_lng DECIMAL(11,8),
  checked_in_at DATETIME,
  notes TEXT,
  status ENUM('draft', 'completed', 'signed') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ic_inspector FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ic_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
  CONSTRAINT fk_ic_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
  CONSTRAINT fk_ic_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_ic_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_ic_qr FOREIGN KEY (qr_code_id) REFERENCES asset_qr_codes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE checklist_questions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED,
  question TEXT NOT NULL,
  answer_type ENUM('yes_no', 'text', 'number') NOT NULL DEFAULT 'yes_no',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cq_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE checklist_answers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  checklist_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  answer_value TEXT,
  description TEXT,
  corrective_action TEXT,
  remarks TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ca_checklist FOREIGN KEY (checklist_id) REFERENCES inspection_checklists(id) ON DELETE CASCADE,
  CONSTRAINT fk_ca_question FOREIGN KEY (question_id) REFERENCES checklist_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 10. INCIDENT REPORTING
-- ============================================================

CREATE TABLE incident_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  group_code VARCHAR(200),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE incident_levels (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  level INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE incidents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(40) NOT NULL UNIQUE,
  reporter_id BIGINT UNSIGNED NOT NULL,
  incident_type_id BIGINT UNSIGNED,
  incident_level_id BIGINT UNSIGNED,
  location_id BIGINT UNSIGNED,
  location_text VARCHAR(255),
  area_id BIGINT UNSIGNED,
  department_id BIGINT UNSIGNED,
  section_id BIGINT UNSIGNED,
  incident_date DATE NOT NULL,
  incident_time TIME,
  description TEXT NOT NULL,
  root_cause TEXT,
  immediate_action TEXT,
  recommendation TEXT,
  corrective_action TEXT,
  review_notes TEXT,
  latitude VARCHAR(200),
  longitude VARCHAR(200),
  status ENUM('reported', 'investigating', 'closed') NOT NULL DEFAULT 'reported',
  is_medical TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_inc_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_inc_type FOREIGN KEY (incident_type_id) REFERENCES incident_types(id) ON DELETE SET NULL,
  CONSTRAINT fk_inc_level FOREIGN KEY (incident_level_id) REFERENCES incident_levels(id) ON DELETE SET NULL,
  CONSTRAINT fk_inc_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
  CONSTRAINT fk_inc_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
  CONSTRAINT fk_inc_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_inc_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL,
  INDEX idx_inc_status (status),
  INDEX idx_inc_date (incident_date)
) ENGINE=InnoDB;

CREATE TABLE incident_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  incident_id BIGINT UNSIGNED NOT NULL,
  image_path TEXT NOT NULL,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ii_incident FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
  CONSTRAINT fk_ii_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE incident_investigations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  incident_id BIGINT UNSIGNED NOT NULL,
  investigation_no VARCHAR(200) NOT NULL,
  investigation_date DATE,
  investigator_id BIGINT UNSIGNED,
  findings TEXT,
  root_cause TEXT,
  recommendations TEXT,
  file_attachment VARCHAR(200),
  status ENUM('open', 'completed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_inv_incident FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
  CONSTRAINT fk_inv_investigator FOREIGN KEY (investigator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 11. MEDICAL / INJURY REPORT
-- ============================================================

CREATE TABLE medical_reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  incident_id BIGINT UNSIGNED,
  report_date DATE NOT NULL,
  case_no VARCHAR(111) NOT NULL,
  patrol_no VARCHAR(111),
  patient_name VARCHAR(111) NOT NULL,
  department_id BIGINT UNSIGNED,
  section_id BIGINT UNSIGNED,
  incident_date DATE,
  incident_time TIME,
  immediate_supervisor VARCHAR(111),
  facility_supervisor VARCHAR(111),
  nature_of_injury TEXT,
  treatment_given VARCHAR(111),
  recommendation VARCHAR(111),
  estimated_lost_days VARCHAR(100),
  restricted_work_days VARCHAR(111),
  injury_classification VARCHAR(111),
  medic_name VARCHAR(111),
  investigator_name VARCHAR(111),
  safety_supervisor VARCHAR(111),
  file_name VARCHAR(200),
  file_type VARCHAR(200),
  file_size VARCHAR(200),
  status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mr_incident FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE SET NULL,
  CONSTRAINT fk_mr_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_mr_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 12. AUDIT LOG
-- ============================================================

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED,
  action VARCHAR(50) NOT NULL,
  module VARCHAR(100) NOT NULL,
  record_id BIGINT UNSIGNED,
  old_data JSON,
  new_data JSON,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_module (module),
  INDEX idx_audit_action (action),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- 13. SYSTEM CONFIGURATION
-- ============================================================

CREATE TABLE system_config (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  config_key VARCHAR(100) NOT NULL UNIQUE,
  config_value TEXT NOT NULL,
  description VARCHAR(255),
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- DEFAULT DATA
-- ============================================================

-- Default Admin User (password: admin123)
-- Note: Generate bcrypt hash via application
INSERT INTO users (name, email, password_hash, role) VALUES
('System Admin', 'admin@sheinspection.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- Default User Groups
INSERT INTO user_groups (name) VALUES
('Super Admin'),
('Admin'),
('Inspector'),
('Viewer');

-- Default Incident Levels
INSERT INTO incident_levels (name, level) VALUES
('Low', 1),
('Medium', 2),
('High', 3),
('Critical', 4);

-- Default Incident Types
INSERT INTO incident_types (name) VALUES
('Fire'),
('Chemical Spill'),
('Injury'),
('Near Miss'),
('Property Damage'),
('Environmental'),
('Security'),
('Other');

-- Default Location Types
INSERT INTO location_types (name) VALUES
('Plant'),
('Warehouse'),
('Office'),
('Workshop'),
('Laboratory'),
('Storage Area');

-- Default Categories
INSERT INTO categories (name) VALUES
('General Safety'),
('Fire Safety'),
('Electrical Safety'),
('Chemical Safety'),
('Machine Safety'),
('Housekeeping'),
('PPE Compliance'),
('Environmental');

-- System Configuration
INSERT INTO system_config (config_key, config_value, description) VALUES
('app_name', 'SHE Inspection System', 'Application name'),
('app_version', '1.0.0', 'Application version'),
('company_name', 'Your Company', 'Default company name'),
('timezone', 'Asia/Bangkok', 'System timezone'),
('inspection_prefix', 'INS', 'Prefix for inspection reference numbers'),
('incident_prefix', 'INC', 'Prefix for incident reference numbers');
