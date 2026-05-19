z-- ============================================================
-- MJ Salon Information System - Database Schema
-- AIS (Accounting Information System) for Salon Business
-- ============================================================

CREATE DATABASE IF NOT EXISTS mj_salon_ais CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mj_salon_ais;

-- ============================================================
-- TABLE: users
-- Stores system users with roles (admin/staff)
-- Internal Control: Role-based access
-- ============================================================
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: customers
-- Stores customer master data
-- ============================================================
CREATE TABLE customers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    phone       VARCHAR(20)  NOT NULL,
    email       VARCHAR(100),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: services
-- Salon services offered with pricing
-- ============================================================
CREATE TABLE services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)   NOT NULL,
    category    VARCHAR(50)    NOT NULL,
    price       DECIMAL(12,2)  NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: transactions
-- Master transaction (receipt header)
-- ============================================================
CREATE TABLE transactions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    receipt_no      VARCHAR(30)    NOT NULL UNIQUE,
    customer_id     INT            NOT NULL,
    staff_id        INT            NOT NULL,
    transaction_date DATE          NOT NULL,
    subtotal        DECIMAL(12,2)  NOT NULL DEFAULT 0,
    discount        DECIMAL(12,2)  NOT NULL DEFAULT 0,
    total_amount    DECIMAL(12,2)  NOT NULL DEFAULT 0,
    payment_method  ENUM('cash','ewallet') NOT NULL DEFAULT 'cash',
    amount_paid     DECIMAL(12,2)  NOT NULL DEFAULT 0,
    change_amount   DECIMAL(12,2)  NOT NULL DEFAULT 0,
    status          ENUM('completed','refunded','void') NOT NULL DEFAULT 'completed',
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (staff_id)    REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: transaction_details
-- Line items for each transaction
-- ============================================================
CREATE TABLE transaction_details (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id  INT            NOT NULL,
    service_id      INT            NOT NULL,
    service_name    VARCHAR(100)   NOT NULL,
    qty             INT            NOT NULL DEFAULT 1,
    unit_price      DECIMAL(12,2)  NOT NULL,
    subtotal        DECIMAL(12,2)  NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (service_id)     REFERENCES services(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: journals
-- Automatic double-entry accounting journal entries
-- AIS Core: Every transaction triggers journal generation
-- ============================================================
CREATE TABLE journals (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id  INT            NOT NULL,
    journal_date    DATE           NOT NULL,
    account_code    VARCHAR(20)    NOT NULL,
    account_name    VARCHAR(100)   NOT NULL,
    debit           DECIMAL(12,2)  NOT NULL DEFAULT 0,
    credit          DECIMAL(12,2)  NOT NULL DEFAULT 0,
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id)
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA: Users
-- Default passwords: admin123 / staff123
-- ============================================================
INSERT INTO users (name, username, password, role) VALUES
('MJ Admin',    'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Sarah Dela Cruz', 'sarah', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Maria Santos',    'maria', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');
-- NOTE: All passwords are 'password' (hashed via bcrypt)
-- For testing use: admin/password  sarah/password  maria/password

-- ============================================================
-- SAMPLE DATA: Customers
-- ============================================================
INSERT INTO customers (name, phone, email) VALUES
('Ana Reyes',      '09171234567', 'ana.reyes@email.com'),
('Bea Santos',     '09182345678', 'bea.santos@email.com'),
('Clara Dela Cruz','09193456789', NULL),
('Diana Ramos',    '09204567890', 'diana.ramos@email.com'),
('Elena Cruz',     '09215678901', NULL);

-- ============================================================
-- SAMPLE DATA: Services
-- ============================================================
INSERT INTO services (name, category, price) VALUES
-- Hair Services
('Haircut (Short)',       'Hair',    150.00),
('Haircut (Long)',        'Hair',    200.00),
('Hair Rebonding',        'Hair',   1200.00),
('Hair Coloring',         'Hair',    800.00),
('Hair Highlights',       'Hair',    600.00),
('Blowdry',              'Hair',    150.00),
('Hot Oil Treatment',     'Hair',    250.00),
-- Nail Services
('Manicure',             'Nails',   120.00),
('Pedicure',             'Nails',   150.00),
('Mani-Pedi Combo',      'Nails',   250.00),
('Nail Extension',       'Nails',   500.00),
('Nail Art (per nail)',  'Nails',    30.00),
-- Facial Services
('Basic Facial',         'Facial',  350.00),
('Whitening Facial',     'Facial',  500.00),
('Acne Treatment',       'Facial',  450.00),
-- Waxing Services
('Eyebrow Threading',    'Waxing',   80.00),
('Upper Lip Wax',        'Waxing',   60.00),
('Underarm Wax',         'Waxing',  150.00);
