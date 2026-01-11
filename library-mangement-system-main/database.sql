-- MySQL schema for Library Management System
-- Database: library_system

CREATE DATABASE IF NOT EXISTS library_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE library_system;

-- Roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (name) VALUES
('admin'),
('librarian'),
('member');

-- Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active','disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_somali VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO categories (name_somali) VALUES
('Taariikh'),
('Af-Soomaali'),
('Diin'),
('Kombiyuutar'),
('Suugaan');

-- Books
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title_somali VARCHAR(255) NOT NULL,
    author_somali VARCHAR(150) NOT NULL,
    category_id INT NOT NULL,
    isbn VARCHAR(50),
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    status ENUM('available','unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

INSERT INTO books (title_somali, author_somali, category_id, isbn, total_copies, available_copies, status) VALUES
('Taariikhda Soomaaliya', 'Prof. Axmed Cali', 1, '978-1234567890', 10, 10, 'available'),
('Af-Soomaaliga Casriga', 'Dr. Fowsiya Cabdi', 2, '978-1234567891', 8, 8, 'available'),
('Gabayadii Sayid Maxamed', 'Sayid Maxamed Cabdulle Xasan', 5, '978-1234567892', 5, 5, 'available'),
('Cilmiga Diinta Islaamka', 'Sh. Cabdiraxmaan Maxamed', 3, '978-1234567893', 12, 12, 'available'),
('Barashada Kombiyuutarka', 'Eng. Xasan Yuusuf', 4, '978-1234567894', 7, 7, 'available'),
('Suugaanta Soomaaliyeed', 'Prof. Maryan Maxamed', 5, '978-1234567895', 6, 6, 'available');

-- Book Issues
CREATE TABLE book_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    member_id INT NOT NULL,
    issued_by INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('issued','returned','lost') DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (member_id) REFERENCES users(id),
    FOREIGN KEY (issued_by) REFERENCES users(id)
);

-- Fines
CREATE TABLE fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    is_paid TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES book_issues(id)
);

-- Logs (simple activity log)
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sample admin, librarian, member users
-- Password for all: 123456 (hash to be generated in PHP or manually)


