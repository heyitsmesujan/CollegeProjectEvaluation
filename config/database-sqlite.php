<?php
try {
    $pdo = new PDO("sqlite:database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE,
            semester TEXT CHECK(semester IN ('4', '6', '8')) NOT NULL,
            academic_year INTEGER DEFAULT NULL,
            batch_year INTEGER NOT NULL,
            student_id TEXT UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER,
            name TEXT NOT NULL,
            description TEXT,
            attendance_records TEXT,
            meeting_times TEXT,
            files TEXT,
            status TEXT CHECK(status IN ('pending_approval', 'approved', 'rejected', 'in_progress', 'completed')) DEFAULT 'pending_approval',
            proposal_status TEXT CHECK(proposal_status IN ('pending', 'approved', 'rejected')) DEFAULT 'pending',
            midterm_status TEXT CHECK(midterm_status IN ('pending', 'approved', 'rejected')) DEFAULT 'pending',
            final_status TEXT CHECK(final_status IN ('pending', 'approved', 'rejected')) DEFAULT 'pending',
            progress_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id)
        );
        
        CREATE TABLE IF NOT EXISTS rubrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            criteria TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS evaluations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER,
            rubric_id INTEGER,
            scores TEXT,
            feedback TEXT,
            total_score REAL,
            grade TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id),
            FOREIGN KEY (rubric_id) REFERENCES rubrics(id)
        );
        
        CREATE TABLE IF NOT EXISTS evaluations_phases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER,
            evaluation_phase TEXT CHECK(evaluation_phase IN ('proposal_defense', 'midterm_defense', 'final_defense')) NOT NULL,
            evaluation_date DATE,
            feedback TEXT,
            score REAL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id)
        );
    ");
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>