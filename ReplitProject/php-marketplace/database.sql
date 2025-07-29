-- PixelTask Marketplace Database Schema
-- Enhanced version with payment management capabilities

-- Create database
CREATE DATABASE IF NOT EXISTS pixeltask_marketplace;
USE pixeltask_marketplace;

-- Users table
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client', 'worker') NOT NULL,
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    skills TEXT,
    company_name VARCHAR(255),
    profile_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks table
CREATE TABLE tasks (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    estimated_time VARCHAR(100),
    spots_available INT NOT NULL DEFAULT 1,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    client_id VARCHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id)
);

-- Transactions table (enhanced for payment management)
CREATE TABLE transactions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    client_id VARCHAR(36) NOT NULL,
    worker_id VARCHAR(36) NOT NULL,
    task_id VARCHAR(36) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    commission DECIMAL(10,2) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    processing_fee DECIMAL(10,2) NOT NULL DEFAULT 0.30,
    worker_payout DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'disputed', 'refunded', 'cancelled') DEFAULT 'pending',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (worker_id) REFERENCES users(id),
    FOREIGN KEY (task_id) REFERENCES tasks(id)
);

-- Disputes table
CREATE TABLE disputes (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    transaction_id VARCHAR(36) NOT NULL,
    reporter_id VARCHAR(36) NOT NULL,
    reason TEXT NOT NULL,
    description TEXT,
    status ENUM('open', 'investigating', 'resolved', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolution TEXT,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (reporter_id) REFERENCES users(id)
);

-- Payouts table
CREATE TABLE payouts (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    worker_id VARCHAR(36) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    payment_method ENUM('jazzcash', 'easypaisa', 'paytm', 'usdt', 'bank_transfer') NOT NULL,
    payment_details TEXT NOT NULL,
    transaction_ids JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    failure_reason TEXT,
    FOREIGN KEY (worker_id) REFERENCES users(id)
);

-- Platform settings table
CREATE TABLE platform_settings (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    processing_fee DECIMAL(10,2) NOT NULL DEFAULT 0.30,
    min_withdrawal DECIMAL(10,2) NOT NULL DEFAULT 3.00,
    min_task_price DECIMAL(10,2) NOT NULL DEFAULT 0.02,
    payout_schedule ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Submissions table (enhanced)
CREATE TABLE submissions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    task_id VARCHAR(36) NOT NULL,
    worker_id VARCHAR(36) NOT NULL,
    proof_text TEXT,
    proof_file_url VARCHAR(500),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    admin_notes TEXT,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (worker_id) REFERENCES users(id)
);

-- Insert admin user
INSERT INTO users (username, email, password, role, wallet_balance, is_active, profile_verified) VALUES
('admin', 'mathfun103@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0.00, TRUE, TRUE);

-- Insert platform settings
INSERT INTO platform_settings (commission_rate, processing_fee, min_withdrawal, min_task_price, payout_schedule) VALUES
(5.00, 0.30, 3.00, 0.02, 'weekly');

-- Sample data for testing
INSERT INTO users (username, email, password, role, wallet_balance, company_name, is_active, profile_verified) VALUES
('sarah_chen', 'sarah.chen@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 100.00, 'Chen Enterprises', TRUE, TRUE),
('david_kim', 'david.kim@startup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 250.00, 'Kim Startup Inc', TRUE, TRUE),
('mike_rodriguez', 'mike.rodriguez@freelancer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker', 45.50, NULL, TRUE, TRUE),
('emma_wilson', 'emma.wilson@designer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker', 78.25, NULL, TRUE, TRUE);

-- Sample tasks
INSERT INTO tasks (title, description, category, price, estimated_time, spots_available, client_id) VALUES
('Website Data Entry', 'Enter product information from images into Excel spreadsheet', 'data entry', 25.00, '2 hours', 3, (SELECT id FROM users WHERE email = 'sarah.chen@email.com')),
('Logo Design Project', 'Create a modern logo for tech startup', 'design', 85.00, '5 hours', 1, (SELECT id FROM users WHERE email = 'david.kim@startup.com')),
('Social Media Content', 'Write engaging posts for Facebook and Instagram', 'writing', 35.00, '1.5 hours', 5, (SELECT id FROM users WHERE email = 'sarah.chen@email.com')),
('Market Research Task', 'Research competitors in the fitness app market', 'research', 45.00, '3 hours', 2, (SELECT id FROM users WHERE email = 'david.kim@startup.com'));

-- Sample transactions
INSERT INTO transactions (transaction_id, client_id, worker_id, task_id, amount, commission, commission_rate, processing_fee, worker_payout, status, description, completed_at) VALUES
('TXN_001', 
 (SELECT id FROM users WHERE email = 'sarah.chen@email.com'), 
 (SELECT id FROM users WHERE email = 'mike.rodriguez@freelancer.com'), 
 (SELECT id FROM tasks WHERE title = 'Website Data Entry'), 
 25.00, 1.25, 5.00, 0.30, 23.45, 'completed', 
 'Payment for Website Data Entry task', 
 NOW() - INTERVAL 2 DAY),

('TXN_002', 
 (SELECT id FROM users WHERE email = 'david.kim@startup.com'), 
 (SELECT id FROM users WHERE email = 'emma.wilson@designer.com'), 
 (SELECT id FROM tasks WHERE title = 'Logo Design Project'), 
 85.00, 4.25, 5.00, 0.30, 80.45, 'pending', 
 'Payment for Logo Design Project', 
 NULL);

-- Sample disputes
INSERT INTO disputes (transaction_id, reporter_id, reason, description, status) VALUES
((SELECT id FROM transactions WHERE transaction_id = 'TXN_002'), 
 (SELECT id FROM users WHERE email = 'david.kim@startup.com'), 
 'Quality concerns', 
 'The delivered logo does not match the requirements specified in the brief', 
 'open');

-- Sample payouts
INSERT INTO payouts (worker_id, amount, status, payment_method, payment_details) VALUES
((SELECT id FROM users WHERE email = 'mike.rodriguez@freelancer.com'), 
 23.45, 'pending', 'bank_transfer', 
 '{"account_number": "1234567890", "bank_name": "First National Bank", "account_holder": "Mike Rodriguez"}');