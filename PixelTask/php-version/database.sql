-- P2PFIY Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS p2pfiy_db;
USE p2pfiy_db;

-- Users table
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'client', 'worker') NOT NULL,
    access_key VARCHAR(255) NOT NULL UNIQUE,
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    skills TEXT,
    company_name VARCHAR(255),
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

-- Submissions table
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

-- Withdrawals table
CREATE TABLE withdrawals (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('jazzcash', 'easypaisa', 'paytm', 'usdt') NOT NULL,
    payment_details TEXT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    admin_notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Transactions table
CREATE TABLE transactions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    task_id VARCHAR(36) NULL,
    submission_id VARCHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (submission_id) REFERENCES submissions(id)
);

-- Insert sample data
INSERT INTO users (name, email, role, access_key, wallet_balance) VALUES
('Admin User', 'admin@p2pfiy.com', 'admin', 'nafisabat103@FR', 0.00);

-- Sample tasks
INSERT INTO tasks (title, description, category, price, estimated_time, spots_available, client_id) VALUES
('Data Entry Task', 'Enter product information into spreadsheet', 'data entry', 2.50, '1 hour', 5, (SELECT id FROM users WHERE role = 'admin')),
('Social Media Post', 'Create engaging social media content', 'social media', 5.00, '30 minutes', 3, (SELECT id FROM users WHERE role = 'admin')),
('Simple Logo Design', 'Design a basic logo for small business', 'design', 15.00, '2 hours', 1, (SELECT id FROM users WHERE role = 'admin'));