# Project Evaluation System

A comprehensive PHP-based system for managing student projects and evaluations with AI feedback capabilities.

## Features

### Core Functionality
- **Project Entry**: Add student project details, attendance, meeting times, and file uploads
- **Rubric Definition**: Create custom evaluation rubrics with multiple criteria
- **Project Evaluation**: Score projects based on defined rubrics with automated grading
- **Results Display**: View evaluation results with scores and feedback
- **Summary Dashboard**: Overview of all projects and performance metrics

### Design Elements
- **Typography**: Belleza (headlines) + Alegreya (body text)
- **Layout**: Clean card-based design with responsive grid system
- **Icons**: Font Awesome icons for intuitive navigation
- **Color Scheme**: Professional blue/gray palette

### AI Integration (Ready for Implementation)
- Gemini API integration structure for AI feedback suggestions
- Genkit framework compatibility for enhanced AI features

## Setup Instructions

1. **Database Setup**:
   ```bash
   mysql -u root -p < config/setup.sql
   ```

2. **Configuration**:
   - Update database credentials in `config/database.php`
   - Ensure `uploads/` directory has write permissions

3. **Web Server**:
   - Place files in web server directory
   - Access via `http://localhost/project-evaluation-system/`

## File Structure
```
project-evaluation-system/
├── config/
│   ├── database.php
│   └── setup.sql
├── includes/
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── uploads/
├── index.php (Dashboard)
├── projects.php (Project Management)
├── rubrics.php (Rubric Creation)
└── evaluations.php (Evaluation Interface)
```

## Usage
1. Create rubrics with evaluation criteria
2. Add student projects with details and files
3. Evaluate projects using defined rubrics
4. View results and performance summaries on dashboard