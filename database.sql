-- =====================================================
-- Employee Directory with QR System - Full Database SQL
-- =====================================================
-- Database: if0_40195878_emp (or your preferred DB name)
-- Run this file to set up the complete database schema
-- =====================================================

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS if0_40195878_emp;
-- USE if0_40195878_emp;

-- =====================================================
-- Table: users
-- Stores admin and user login credentials
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: employees
-- Main employee records table with all personal,
-- contact, employment, and family information
-- =====================================================
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE,
    first_name VARCHAR(100),
    middle_name VARCHAR(100),
    last_name VARCHAR(100),
    full_name VARCHAR(300),
    nickname VARCHAR(50),
    date_of_birth DATE NULL,
    age INT DEFAULT 0,
    place_of_birth VARCHAR(200),
    gender VARCHAR(20) DEFAULT 'Male',
    mobile_number VARCHAR(20),
    telephone_number VARCHAR(20),
    email VARCHAR(254),
    present_address TEXT,
    permanent_address TEXT,
    civil_status VARCHAR(20),
    religion VARCHAR(50),
    position VARCHAR(100),
    department VARCHAR(100),
    branch VARCHAR(100),
    company VARCHAR(100),
    employment_status VARCHAR(50),
    basic_salary DECIMAL(12,2) DEFAULT 0.00,
    rate DECIMAL(12,2) DEFAULT 0.00,
    allowances DECIMAL(12,2) DEFAULT 0.00,
    pay_frequency VARCHAR(20),
    sss_no VARCHAR(50),
    tin VARCHAR(50),
    pagibig_mid VARCHAR(50),
    philhealth_no VARCHAR(50),
    spouse_name VARCHAR(100),
    spouse_occupation VARCHAR(100),
    father_name VARCHAR(100),
    father_occupation VARCHAR(100),
    mother_name VARCHAR(100),
    mother_occupation VARCHAR(100),
    number_of_siblings INT DEFAULT 0,
    relative_working VARCHAR(10),
    relative_details TEXT,
    emergency_name VARCHAR(100),
    emergency_address TEXT,
    emergency_contact VARCHAR(50),
    date_hired DATE NULL,
    photo_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: activity_logs
-- Tracks user actions for audit trail
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Default Admin User
-- Username: admin | Password: admin1234
-- (password is bcrypt hashed - use setup.php or
--  reset_password.php to set the actual hash)
-- =====================================================
-- NOTE: Do NOT use this INSERT directly.
-- Instead, run setup.php or reset_password.php to create
-- the admin user with a properly hashed password.
-- The line below is for reference only:
--
-- INSERT INTO users (username, password, role)
-- VALUES ('admin', '$2y$10$...hashed...', 'admin');

-- =====================================================
-- Indexes for better query performance
-- =====================================================
CREATE INDEX idx_employees_employee_id ON employees(employee_id);
CREATE INDEX idx_employees_department ON employees(department);
CREATE INDEX idx_employees_status ON employees(employment_status);
CREATE INDEX idx_employees_name ON employees(first_name, last_name);
CREATE INDEX idx_employees_email ON employees(email);
CREATE INDEX idx_activity_user ON activity_logs(user_id);
CREATE INDEX idx_activity_action ON activity_logs(action);
CREATE INDEX idx_activity_date ON activity_logs(created_at);

-- =====================================================
-- How to use:
-- 1. Import this file into your MySQL database:
--    mysql -u your_username -p your_database < database.sql
--
-- 2. Update your .env file with correct DB credentials:
--    DB_HOST=your_host
--    DB_USERNAME=your_username
--    DB_PASSWORD=your_password
--    DB_NAME=your_database
--
-- 3. Run setup.php in your browser to create the admin user
--    or use reset_password.php to reset to admin/admin1234
-- =====================================================
