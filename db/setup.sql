-- PapaGroup Database Schema
-- Run this SQL in phpMyAdmin.

CREATE DATABASE IF NOT EXISTS papagroup CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE papagroup;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  role ENUM('user','manager') NOT NULL DEFAULT 'user',
  verified TINYINT(1) NOT NULL DEFAULT 0,
  rating INT NOT NULL DEFAULT 0,
  total_ratings INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cars (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id INT UNSIGNED NOT NULL,
  brand VARCHAR(50) NOT NULL,
  model VARCHAR(100) NOT NULL,
  year INT NOT NULL,
  transmission VARCHAR(20) NOT NULL,
  variant VARCHAR(100) NOT NULL,
  mileage INT NOT NULL,
  price DECIMAL(12, 2) NOT NULL,
  image LONGBLOB,
  image_path VARCHAR(255),
  city VARCHAR(100) NOT NULL,
  status ENUM('pending','approved','rejected','sold') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_seller (seller_id),
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  car_id INT UNSIGNED NOT NULL,
  buyer_id INT UNSIGNED NOT NULL,
  seller_id INT UNSIGNED NOT NULL,
  purchase_price DECIMAL(12, 2) NOT NULL,
  transaction_status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  payment_date TIMESTAMP NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_buyer (buyer_id),
  INDEX idx_seller (seller_id),
  INDEX idx_car (car_id),
  INDEX idx_status (transaction_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  car_id INT UNSIGNED NOT NULL,
  reviewer_id INT UNSIGNED NOT NULL,
  rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_car (car_id),
  INDEX idx_reviewer (reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
