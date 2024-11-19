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
    '$2y$10$c20i/yJHgWBx3erDMFNK4uXQ8Bj71R3GcynHNruocIVCeSa6pEkGO', -- Replace with a hashed password
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

-- Add a new column to track election creation
-- Step 1: Add the column allowing NULL temporarily
ALTER TABLE elections 
ADD COLUMN created_by INT NULL AFTER description;

-- Step 2: Populate the column with a valid user ID
-- Assuming the admin user ID is 1
UPDATE elections 
SET created_by = 1; -- Replace '1' with an actual admin user ID from the `users` table

-- Step 3: Alter the column to NOT NULL and add the foreign key
ALTER TABLE elections 
MODIFY COLUMN created_by INT NOT NULL,
ADD CONSTRAINT fk_elections_created_by 
FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE;


-- Add form submission tracking table
CREATE TABLE election_forms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    user_id INT NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    form_data JSON NOT NULL,
    reviewed_by INT,
    review_date TIMESTAMP NULL,
    review_comments TEXT,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_submission (election_id, user_id)
);

-- Drop existing procedures
DROP PROCEDURE IF EXISTS UpdateElectionStatus;
DROP PROCEDURE IF EXISTS UpdateAccountStatus;

-- Updated procedure for election management
DELIMITER //

CREATE PROCEDURE CreateElection(
    IN admin_id INT,
    IN election_title VARCHAR(100),
    IN election_description TEXT,
    IN election_start DATETIME,
    IN election_end DATETIME
)
BEGIN
    DECLARE admin_role INT;
    
    -- Check if user is admin
    SELECT role_id INTO admin_role FROM users WHERE id = admin_id;
    
    IF admin_role != 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only administrators can create elections';
    END IF;
    
    -- Create the election
    INSERT INTO elections (
        title,
        description,
        created_by,
        start_date,
        end_date,
        status
    ) VALUES (
        election_title,
        election_description,
        admin_id,
        election_start,
        election_end,
        'upcoming'
    );
    
    -- Log the action
    INSERT INTO admin_logs (
        admin_id,
        action_type,
        target_id,
        action_details
    ) VALUES (
        admin_id,
        'update_election_status',
        LAST_INSERT_ID(),
        'Created new election'
    );
END//

CREATE PROCEDURE UpdateElectionStatus(
    IN admin_id INT,
    IN election_id INT,
    IN new_status ENUM('upcoming', 'ongoing', 'completed')
)
BEGIN
    DECLARE admin_role INT;
    DECLARE current_status VARCHAR(20);
    
    -- Check if user is admin
    SELECT role_id INTO admin_role FROM users WHERE id = admin_id;
    SELECT status INTO current_status FROM elections WHERE id = election_id;
    
    IF admin_role != 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only administrators can update election status';
    END IF;
    
    -- Validate status transition
    IF current_status = 'completed' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot modify completed elections';
    END IF;
    
    -- Update the status
    UPDATE elections 
    SET 
        status = new_status,
        updated_by = admin_id
    WHERE id = election_id;
    
    -- Log the action
    INSERT INTO admin_logs (
        admin_id,
        action_type,
        target_id,
        action_details
    ) VALUES (
        admin_id,
        'update_election_status',
        election_id,
        CONCAT('Changed status from ', current_status, ' to ', new_status)
    );
END//

CREATE PROCEDURE SubmitElectionForm(
    IN user_id INT,
    IN election_id INT,
    IN form_data JSON
)
BEGIN
    DECLARE election_status VARCHAR(20);
    
    -- Check if election is ongoing
    SELECT status INTO election_status FROM elections WHERE id = election_id;
    
    IF election_status != 'ongoing' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Forms can only be submitted during ongoing elections';
    END IF;
    
    -- Check if user already submitted
    IF EXISTS (SELECT 1 FROM election_forms 
               WHERE user_id = user_id 
               AND election_id = election_id 
               AND status != 'draft') THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'You have already submitted a form for this election';
    END IF;
    
    -- Insert or update the form
    INSERT INTO election_forms (
        election_id,
        user_id,
        form_data,
        status
    ) VALUES (
        election_id,
        user_id,
        form_data,
        'submitted'
    )
    ON DUPLICATE KEY UPDATE
        form_data = VALUES(form_data),
        status = 'submitted',
        submission_date = CURRENT_TIMESTAMP;
END//

CREATE PROCEDURE ReviewElectionForm(
    IN admin_id INT,
    IN form_id INT,
    IN review_status ENUM('approved', 'rejected'),
    IN comments TEXT
)
BEGIN
    DECLARE admin_role INT;
    
    -- Check if user is admin
    SELECT role_id INTO admin_role FROM users WHERE id = admin_id;
    
    IF admin_role != 1 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Only administrators can review forms';
    END IF;
    
    -- Update the form status
    UPDATE election_forms
    SET 
        status = review_status,
        reviewed_by = admin_id,
        review_date = CURRENT_TIMESTAMP,
        review_comments = comments
    WHERE id = form_id;
END//

CREATE PROCEDURE UpdateAccountStatus(
    IN admin_id INT,
    IN target_user_id INT,
    IN new_status ENUM('active', 'inactive', 'suspended')
)
BEGIN
    DECLARE admin_role INT;
    
    -- Check if user is admin
    SELECT role_id INTO admin_role FROM users WHERE id = admin_id;
    
    IF admin_role != 1 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Only administrators can update account status';
    END IF;
    
    -- Update the user status
    UPDATE users 
    SET 
        account_status = new_status,
        updated_by = admin_id
    WHERE id = target_user_id;
    
    -- Log the action
    INSERT INTO admin_logs (
        admin_id,
        action_type,
        target_id,
        action_details
    ) VALUES (
        admin_id,
        'update_user_status',
        target_user_id,
        CONCAT('Changed account status to ', new_status)
    );
END//

DELIMITER ;

-- Add triggers for additional security
DELIMITER //

CREATE TRIGGER before_election_update
BEFORE UPDATE ON elections
FOR EACH ROW
BEGIN
    DECLARE updater_role INT;
    
    SELECT role_id INTO updater_role 
    FROM users 
    WHERE id = NEW.updated_by;
    
    IF updater_role != 1 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Only administrators can modify elections';
    END IF;
END//

CREATE TRIGGER before_election_insert
BEFORE INSERT ON elections
FOR EACH ROW
BEGIN
    DECLARE creator_role INT;
    
    SELECT role_id INTO creator_role 
    FROM users 
    WHERE id = NEW.created_by;
    
    IF creator_role != 1 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Only administrators can create elections';
    END IF;
END//

DELIMITER ;

-- Create views for easy access to election data
CREATE VIEW active_elections AS
SELECT 
    e.*,
    u.first_name as created_by_name,
    u.email as created_by_email
FROM elections e
JOIN users u ON e.created_by = u.id
WHERE e.status = 'ongoing';

CREATE VIEW election_form_summary AS
SELECT 
    ef.*,
    e.title as election_title,
    u.first_name as user_first_name,
    u.last_name as user_last_name,
    a.first_name as reviewer_first_name,
    a.last_name as reviewer_last_name
FROM election_forms ef
JOIN elections e ON ef.election_id = e.id
JOIN users u ON ef.user_id = u.id
LEFT JOIN users a ON ef.reviewed_by = a.id;

CREATE TABLE constituencies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    state VARCHAR(50) NOT NULL,
    district VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add foreign key to users table
ALTER TABLE users 
ADD CONSTRAINT fk_user_constituency
FOREIGN KEY (constituency_id) REFERENCES constituencies(id);

-- Modify election_forms table to reference constituencies table
ALTER TABLE election_forms
ADD COLUMN constituency_id INT,
ADD CONSTRAINT fk_election_form_constituency
FOREIGN KEY (constituency_id) REFERENCES constituencies(id);

DELIMITER //

DROP PROCEDURE IF EXISTS SubmitElectionForm//

CREATE PROCEDURE SubmitElectionForm(
    IN user_id INT,
    IN election_id INT,
    IN form_data JSON,
    IN constituency_id INT
)
BEGIN
    DECLARE election_status VARCHAR(20);
    
    -- Check if election is ongoing
    SELECT status INTO election_status FROM elections WHERE id = election_id;
    
    IF election_status != 'ongoing' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Forms can only be submitted during ongoing elections';
    END IF;
    
    -- Check if user already submitted
    IF EXISTS (SELECT 1 FROM election_forms 
               WHERE user_id = user_id 
               AND election_id = election_id 
               AND status != 'draft') THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'You have already submitted a form for this election';
    END IF;
    
    -- Insert or update the form
    INSERT INTO election_forms (
        election_id,
        user_id,
        constituency_id,
        form_data,
        status
    ) VALUES (
        election_id,
        user_id,
        constituency_id,
        form_data,
        'submitted'
    )
    ON DUPLICATE KEY UPDATE
        form_data = VALUES(form_data),
        constituency_id = VALUES(constituency_id),
        status = 'submitted',
        submission_date = CURRENT_TIMESTAMP;
END//

DELIMITER ;

ALTER TABLE votes 
ADD COLUMN election_form_id INT,
ADD COLUMN constituency_id INT,
ADD FOREIGN KEY (election_form_id) REFERENCES election_forms(id) ON DELETE SET NULL,
ADD FOREIGN KEY (constituency_id) REFERENCES constituencies(id) ON DELETE SET NULL;