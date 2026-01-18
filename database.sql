-- ===============================
-- CREATE DATABASE
-- ===============================
CREATE DATABASE IF NOT EXISTS parlor_system;
USE parlor_system;

-- ===============================
-- USERS (Admin + Managers)
-- ===============================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin','manager') DEFAULT 'manager',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===============================
-- SERVICES (Editable / Soft Delete)
-- ===============================
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_minutes INT DEFAULT 30,
    staff_assigned VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        ON UPDATE CURRENT_TIMESTAMP
);

-- ===============================
-- CUSTOMERS (Optional / Walk-in)
-- ===============================
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150),
    contact_number VARCHAR(20),
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===============================
-- TRANSACTIONS (Payments)
-- ===============================
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    manager_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_method ENUM('cash','gcash','card') NOT NULL,
    notes TEXT,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) 
        REFERENCES customers(id) 
        ON DELETE SET NULL,

    FOREIGN KEY (manager_id) 
        REFERENCES users(id)
);

-- ===============================
-- TRANSACTION SERVICES (Multi-service per transaction)
-- ===============================
CREATE TABLE transaction_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    service_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (transaction_id) 
        REFERENCES transactions(id) 
        ON DELETE CASCADE,

    FOREIGN KEY (service_id) 
        REFERENCES services(id)
);

-- ===============================
-- DAILY INCOME QUOTA
-- ===============================
CREATE TABLE daily_quota (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quota_amount DECIMAL(10,2) NOT NULL,
    date DATE UNIQUE NOT NULL
);

-- ===============================
-- SEED DATA (Admin + 4 Managers)
-- ===============================
-- Default password for all users: "password123"
-- Password hash generated using PHP password_hash() with PASSWORD_DEFAULT
INSERT INTO users (username, password, name, role) VALUES

-- ===============================
-- SAMPLE SERVICES
-- ===============================
INSERT INTO services (service_name, description, price, duration_minutes, staff_assigned) VALUES
('Haircut', 'Basic haircut and styling', 250.00, 45, 'Any Stylist'),
('Hair Coloring', 'Full hair color treatment', 1500.00, 120, 'Senior Stylist'),
('Manicure', 'Basic nail care and polish', 300.00, 30, 'Nail Technician'),
('Pedicure', 'Foot spa and nail treatment', 400.00, 45, 'Nail Technician'),
('Facial Treatment', 'Deep cleansing facial', 800.00, 60, 'Esthetician'),
('Hair Rebonding', 'Hair straightening treatment', 2500.00, 180, 'Senior Stylist'),
('Makeup Service', 'Professional makeup application', 1200.00, 60, 'Makeup Artist'),
('Waxing', 'Hair removal service', 500.00, 30, 'Esthetician'),
('Hair Spa', 'Deep conditioning treatment', 600.00, 45, 'Any Stylist'),
('Eyelash Extension', 'Individual lash application', 1000.00, 90, 'Lash Technician');

-- ===============================
-- SAMPLE DAILY QUOTA
-- ===============================
INSERT INTO daily_quota (quota_amount, date) VALUES
(5000.00, CURDATE()),
(5000.00, DATE_ADD(CURDATE(), INTERVAL 1 DAY)),
(5000.00, DATE_ADD(CURDATE(), INTERVAL 2 DAY));
