-- Create database
CREATE DATABASE IF NOT EXISTS exam_online;
USE exam_online;

-- Create users table with role
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher') NOT NULL DEFAULT 'student',
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create exams table
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    teacher_id INT NOT NULL,
    duration INT NOT NULL, -- in minutes
    passing_score INT NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'checkbox', 'text') NOT NULL,
    points INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create answers table
CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create student_exams table (for tracking exam attempts)
CREATE TABLE IF NOT EXISTS student_exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    score INT NULL,
    status ENUM('in_progress', 'passed', 'failed') NOT NULL DEFAULT 'in_progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create student_answers table
CREATE TABLE IF NOT EXISTS student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_exam_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_id INT NULL, -- NULL for text answers
    text_answer TEXT NULL, -- for text type questions
    is_correct BOOLEAN NULL, -- NULL until graded
    points_earned INT NULL, -- NULL until graded
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_exam_id) REFERENCES student_exams(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id),
    FOREIGN KEY (answer_id) REFERENCES answers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 


USE exam_online;
create DATABASE exam_online ; 

select * from users ; 




DROP TABLE IF EXISTS answers;
DROP TABLE IF EXISTS questions;

-- Create questions table with correct columns
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    name VARCHAR(255) NOT NULL,  -- Changed from question_name to name
    points INT NOT NULL DEFAULT 1,
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);

-- Create answers table
DROP TABLE IF EXISTS answers;
CREATE TABLE answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    answer_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id)
);
ALTER TABLE questions ADD COLUMN name VARCHAR(255) NOT NULL AFTER question_text;
alter table exams add column date date not null ;
DROP TABLE IF EXISTS answers;

-- select * from users ; 
describe users ;



-- Add exam_date column to exams table
ALTER TABLE exams ADD COLUMN exam_date DATE NOT NULL;

-- Add enrollment_date column to users table if not exists
ALTER TABLE users ADD COLUMN enrollment_date DATE NOT NULL DEFAULT CURRENT_DATE;

-- Update existing exams to have a date (if you have existing data)
UPDATE exams SET exam_date = CURRENT_DATE WHERE exam_date IS NULL;


SELECT * FROM users WHERE id = teacher_id;

describe users ;
SET SQL_SAFE_UPDATES = 0;

UPDATE exams SET exam_date = CURRENT_DATE WHERE exam_date IS NULL;




CREATE TABLE security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    attempt_id INT,
    incident_type VARCHAR(50),
    timestamp DATETIME,
    FOREIGN KEY (exam_id) REFERENCES exams(id),
    FOREIGN KEY (attempt_id) REFERENCES student_exams(id)
);

CREATE TABLE surveillance_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    attempt_id INT,
    filename VARCHAR(255),
    timestamp DATETIME,
    FOREIGN KEY (exam_id) REFERENCES exams(id),
    FOREIGN KEY (attempt_id) REFERENCES student_exams(id)
);



-- Add new table exam_questions to manage question pools for each exam
CREATE TABLE IF NOT EXISTS exam_questions (
    exam_id INT,
    question_id INT,
    question_weight DECIMAL(5,2) DEFAULT 1.00,
    PRIMARY KEY (exam_id, question_id),
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add questions_per_student column to exams table
ALTER TABLE exams
ADD COLUMN questions_per_student INT DEFAULT 0 AFTER duration;

-- Add random_seed column to attempts table
ALTER TABLE attempts
ADD COLUMN random_seed VARCHAR(255) AFTER question_order;

-- Migrate existing questions to exam_questions table
INSERT INTO exam_questions (exam_id, question_id, question_weight)
SELECT DISTINCT e.id, q.id, 1.00
FROM exams e
CROSS JOIN questions q
WHERE e.id IN (SELECT id FROM exams)
AND q.id IN (SELECT id FROM questions);



-- Create attempts table to store exam attempts by students
CREATE TABLE IF NOT EXISTS attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    question_order JSON,
    random_seed VARCHAR(255),
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL,
    status ENUM('in_progress', 'submitted', 'graded') DEFAULT 'in_progress',
    total_score DECIMAL(5,2) DEFAULT 0.00,
    time_spent INT DEFAULT 0,  -- Time spent in seconds
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    
    -- Indexes for better query performance
    INDEX idx_user_exam (user_id, exam_id),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create table for storing student answers
CREATE TABLE IF NOT EXISTS student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_text TEXT,
    score DECIMAL(5,2) DEFAULT NULL,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    
    -- Unique constraint to prevent duplicate answers
    UNIQUE KEY unique_attempt_question (attempt_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;