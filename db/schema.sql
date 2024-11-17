-- Create database
CREATE DATABASE votadhikar;
USE votadhikar;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aadhaar_number VARCHAR(12) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('M', 'F', 'O') NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    constituency_id INT,
    voter_id VARCHAR(10) UNIQUE NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    account_status ENUM('active', 'inactive', 'suspended') DEFAULT 'inactive'
);

-- Elections table
CREATE TABLE elections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming'
);

-- Political Parties table
CREATE TABLE political_parties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    symbol_url VARCHAR(255),
    description TEXT
);

-- Candidates table
CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    party_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    biography TEXT,
    photo_url VARCHAR(255),
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (party_id) REFERENCES political_parties(id) ON DELETE CASCADE
);

-- Votes table
CREATE TABLE votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    voter_id INT NOT NULL,
    candidate_id INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    voting_station_id INT,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (voter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (election_id, voter_id)
);

-- Exit Polls table
CREATE TABLE exit_polls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    user_id INT NOT NULL,
    response TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Authentication Logs table
CREATE TABLE auth_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    login_status ENUM('success', 'failed') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password Reset Tokens table
CREATE TABLE password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expiry_date TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_aadhaar ON users(aadhaar_number);
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_election_date ON elections(start_date, end_date);
CREATE INDEX idx_vote_election ON votes(election_id);
CREATE INDEX idx_vote_voter ON votes(voter_id);

-- Create roles table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL
);

-- Add a role_id column to users table
ALTER TABLE users 
ADD COLUMN role_id INT DEFAULT 2, -- Default role is "user"
ADD FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- Insert roles: admin and user
INSERT INTO roles (name) VALUES ('admin'), ('user');

-- Admin Actions Log Table
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action_type ENUM('update_user_status', 'update_election_status') NOT NULL,
    target_id INT NOT NULL, -- Could be user_id or election_id depending on action
    action_details TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Allow users to manage account statuses
ALTER TABLE users 
ADD COLUMN updated_by INT NULL,
ADD FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

-- Update elections table to track the admin who last updated the status
ALTER TABLE elections 
ADD COLUMN updated_by INT NULL,
ADD FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

-- Insert an admin user for demonstration
INSERT INTO users (
    aadhaar_number, 
    password, 
    first_name, 
    last_name, 
    date_of_birth, 
    gender, 
    email, 
    phone, 
    address, 
    constituency_id, 
    voter_id, 
    is_verified, 
    role_id, 
    account_status
) 
VALUES (
    '123456789012', -- Example Aadhaar
    'hashed_password', -- Replace with a hashed password
    'Admin',
    'User',
    '1980-01-01',
    'M',
    'admin@example.com',
    '1234567890',
    'Admin Address',
    NULL, -- Constituency is not relevant for admin
    3000,
    TRUE,
    1, -- Admin role
    'active'
);

-- Grant admin permissions via a procedure for better management
DELIMITER //
CREATE PROCEDURE UpdateAccountStatus(
    IN adminId INT, 
    IN userId INT, 
    IN newStatus ENUM('active', 'inactive', 'suspended')
)
BEGIN
    DECLARE adminRole INT;
    SELECT role_id INTO adminRole FROM users WHERE id = adminId;
    
    IF adminRole = 1 THEN
        UPDATE users SET account_status = newStatus, updated_by = adminId WHERE id = userId;
        INSERT INTO admin_logs (admin_id, action_type, target_id, action_details)
        VALUES (adminId, 'update_user_status', userId, CONCAT('Changed status to ', newStatus));
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unauthorized action.';
    END IF;
END//
DELIMITER ;

DELIMITER //
CREATE PROCEDURE UpdateElectionStatus(
    IN adminId INT, 
    IN electionId INT, 
    IN newStatus ENUM('upcoming', 'ongoing', 'completed')
)
BEGIN
    DECLARE adminRole INT;
    SELECT role_id INTO adminRole FROM users WHERE id = adminId;

    IF adminRole = 1 THEN
        UPDATE elections SET status = newStatus, updated_by = adminId WHERE id = electionId;
        INSERT INTO admin_logs (admin_id, action_type, target_id, action_details)
        VALUES (adminId, 'update_election_status', electionId, CONCAT('Changed status to ', newStatus));
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unauthorized action.';
    END IF;
END//
DELIMITER ;

