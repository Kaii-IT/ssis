CREATE DATABASE IF NOT EXISTS ssis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ssis;

CREATE TABLE departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(15) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  kind ENUM('college','office') NOT NULL DEFAULT 'college',
  is_clearing TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_no VARCHAR(30) NOT NULL UNIQUE,
  role ENUM('student','registrar','cashier','department','admin') NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL,
  email VARCHAR(120) NULL UNIQUE,
  department_id INT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE students (
  user_id INT UNSIGNED PRIMARY KEY,
  department_id INT UNSIGNED NOT NULL,
  program VARCHAR(120) NOT NULL,
  year_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('regular','irregular','graduated','inactive') NOT NULL DEFAULT 'regular',
  contact_no VARCHAR(30) NULL,
  address VARCHAR(255) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE terms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE,
  is_current TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(15) NOT NULL UNIQUE,
  title VARCHAR(150) NOT NULL,
  units TINYINT UNSIGNED NOT NULL,
  department_id INT UNSIGNED NULL,          -- NULL = general education (open to all colleges)
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE offerings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  term_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  section VARCHAR(10) NOT NULL,
  schedule VARCHAR(60) NOT NULL,
  room VARCHAR(30) NOT NULL,
  slots SMALLINT UNSIGNED NOT NULL,
  UNIQUE (term_id, subject_id, section),
  FOREIGN KEY (term_id) REFERENCES terms(id),
  FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB;

CREATE TABLE enrollments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  term_id INT UNSIGNED NOT NULL,
  status ENUM('draft','submitted','approved') NOT NULL DEFAULT 'draft',
  remarks VARCHAR(255) NULL,
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (student_id, term_id),
  FOREIGN KEY (student_id) REFERENCES users(id),
  FOREIGN KEY (term_id) REFERENCES terms(id)
) ENGINE=InnoDB;

CREATE TABLE enrollment_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enrollment_id INT UNSIGNED NOT NULL,
  offering_id INT UNSIGNED NOT NULL,
  grade VARCHAR(4) NULL,
  remarks VARCHAR(12) NULL,                  -- Passed / Failed / Incomplete / Dropped
  UNIQUE (enrollment_id, offering_id),
  FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
  FOREIGN KEY (offering_id) REFERENCES offerings(id)
) ENGINE=InnoDB;

CREATE TABLE clearance_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  term_id INT UNSIGNED NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  status ENUM('pending','cleared','hold') NOT NULL DEFAULT 'pending',
  remarks VARCHAR(255) NULL,
  processed_by INT UNSIGNED NULL,
  processed_at DATETIME NULL,
  UNIQUE (student_id, term_id, department_id),
  FOREIGN KEY (student_id) REFERENCES users(id),
  FOREIGN KEY (term_id) REFERENCES terms(id),
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE document_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  fee DECIMAL(10,2) NOT NULL,
  requirement ENUM('none','enrollment','clearance') NOT NULL DEFAULT 'none',
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE document_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(20) NULL UNIQUE,
  student_id INT UNSIGNED NOT NULL,
  document_type_id INT UNSIGNED NOT NULL,
  copies TINYINT UNSIGNED NOT NULL DEFAULT 1,
  purpose VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending_payment','paid','processing','ready','released','rejected','cancelled') NOT NULL DEFAULT 'pending_payment',
  remarks VARCHAR(255) NULL,
  processed_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id),
  FOREIGN KEY (document_type_id) REFERENCES document_types(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  payment_type ENUM('tuition','document') NOT NULL,
  document_request_id INT UNSIGNED NULL,
  enrollment_id INT UNSIGNED NULL,
  description VARCHAR(160) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method ENUM('cash','e-wallet','bank') NULL,
  reference_no VARCHAR(30) NULL,
  status ENUM('unpaid','for_verification','paid','rejected','void') NOT NULL DEFAULT 'unpaid',
  or_number VARCHAR(30) NULL UNIQUE,
  remarks VARCHAR(255) NULL,
  verified_by INT UNSIGNED NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id),
  FOREIGN KEY (document_request_id) REFERENCES document_requests(id),
  FOREIGN KEY (enrollment_id) REFERENCES enrollments(id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(40) NOT NULL,
  entity VARCHAR(30) NULL,
  entity_id INT UNSIGNED NULL,
  details VARCHAR(255) NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at)
) ENGINE=InnoDB;