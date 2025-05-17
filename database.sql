CREATE DATABASE dronesafe;
USE dronesafe;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    drone_id VARCHAR(20) NOT NULL,
    pickup_code CHAR(4) NOT NULL,
    status ENUM('processing', 'in_transit', 'ready_for_pickup', 'delivered') DEFAULT 'processing',
    estimated_delivery DATETIME,
    actual_delivery DATETIME,
    pickup_location VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE drone_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drone_id VARCHAR(20) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);