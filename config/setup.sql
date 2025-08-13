CREATE DATABASE IF NOT EXISTS project_evaluation;
USE project_evaluation;

CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    semester ENUM('4', '6', '8') NOT NULL,
    batch_year YEAR NOT NULL,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    attendance_records TEXT,
    meeting_times TEXT,
    files TEXT,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    progress_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE demos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT,
    demo_type ENUM('regular', 'proposal_defense', 'midterm_defense') NOT NULL,
    demo_date DATE,
    feedback TEXT,
    score DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE rubrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    criteria JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT,
    rubric_id INT,
    scores JSON,
    feedback TEXT,
    total_score DECIMAL(5,2),
    grade VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (rubric_id) REFERENCES rubrics(id)
);