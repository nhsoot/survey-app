-- Database: survey_app
CREATE DATABASE IF NOT EXISTS survey_app;
USE survey_app;

-- Tabel admins
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel surveys
CREATE TABLE surveys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive', 'draft') DEFAULT 'draft',
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Tabel questions
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    question_text TEXT NOT NULL,
    type ENUM('multiple_choice', 'likert', 'text', 'paragraph') NOT NULL,
    options JSON,
    is_required BOOLEAN DEFAULT TRUE,
    order_number INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

-- Tabel responses
CREATE TABLE responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    respondent_token VARCHAR(64) UNIQUE NOT NULL,
    respondent_name VARCHAR(100),
    respondent_email VARCHAR(100),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

-- Tabel answers
CREATE TABLE answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    response_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Insert default admin (password: admin123)
INSERT INTO admins (username, password, email) VALUES 
('admin', '$2y$10$YourHashedPasswordHere', 'admin@survey.com');

-- Sample data for testing
INSERT INTO surveys (admin_id, title, description, status) VALUES 
(1, 'Survey Kepuasan Dosen 2024', 'Survey untuk mengukur kepuasan dosen terhadap berbagai program', 'active');

INSERT INTO questions (survey_id, question_text, type, options, order_number) VALUES 
(1, 'Bagaimana kepuasan Anda terhadap program Studi Lanjut?', 'likert', '["Sangat Puas","Puas","Cukup","Kurang","Sangat Kurang"]', 1),
(1, 'Bagaimana kepuasan Anda terhadap Pengurusan JFA?', 'likert', '["Sangat Puas","Puas","Cukup","Kurang","Sangat Kurang"]', 2),
(1, 'Bagaimana kepuasan Anda terhadap Pelatihan/Training?', 'likert', '["Sangat Puas","Puas","Cukup","Kurang","Sangat Kurang"]', 3);