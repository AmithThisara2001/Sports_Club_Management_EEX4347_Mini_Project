CREATE DATABASE sports_club;
USE sports_club;

-- Members Table
CREATE TABLE members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Admin Table
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role VARCHAR(50) DEFAULT 'admin'
);

-- Events Table
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    event_type ENUM('football', 'cricket', 'tournament', 'other') NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(100),
    max_participants INT DEFAULT 20,
    current_participants INT DEFAULT 0,
    description TEXT,
    created_by INT,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(admin_id)
);

-- Equipment Table
CREATE TABLE equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_name VARCHAR(100) NOT NULL,
    equipment_type VARCHAR(50),
    quantity_total INT NOT NULL,
    quantity_available INT NOT NULL,
    condition_status ENUM('good', 'fair', 'poor') DEFAULT 'good',
    location VARCHAR(100)
);

-- Event Registration Table
CREATE TABLE event_registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    event_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (member_id, event_id)
);

-- Equipment Bookings Table
CREATE TABLE equipment_bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    equipment_id INT NOT NULL,
    booking_date DATE NOT NULL,
    return_date DATE NOT NULL,
    actual_return_date DATE,
    quantity INT NOT NULL,
    status ENUM('pending', 'approved', 'returned', 'overdue') DEFAULT 'pending',
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE
);

-- Messages Table
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT,
    subject VARCHAR(200) NOT NULL,
    message_text TEXT NOT NULL,
    sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_status BOOLEAN DEFAULT FALSE,
    reply_text TEXT,
    FOREIGN KEY (sender_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES admin(admin_id) ON DELETE SET NULL
);

-- Insert sample admin
INSERT INTO admin (username, password, full_name, email) 
VALUES ('admin', '$2y$10$example_hashed_password', 'System Admin', 'admin@sportsclub.com');

-- Insert sample equipment
INSERT INTO equipment (equipment_name, equipment_type, quantity_total, quantity_available, location) 
VALUES 
('Football', 'Ball', 10, 10, 'Equipment Room A'),
('Cricket Bat', 'Bat', 15, 15, 'Equipment Room B'),
('Tennis Racket', 'Racket', 8, 8, 'Equipment Room A');