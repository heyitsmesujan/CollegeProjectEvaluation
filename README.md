# Project Evaluation System

A comprehensive PHP-based system for managing student projects and evaluations with AI feedback capabilities.

## Features

### Core Functionality
- **Student Management**: Import/add students with batch and semester tracking
- **Project Topic Management**: Student topic submission with approval workflow
- **Multi-Phase Evaluation**: Proposal Defense (0-20), Midterm Defense (0-40), Final Defense (0-20)
- **Auto-Upgrade System**: Automatic semester progression upon project completion
- **AI Performance Analysis**: Intelligent feedback generation based on evaluations
- **Batch Management**: Organize students by semester and batch year
- **Search & Filter**: Advanced filtering across projects, students, and batches

### Student Portal
- **Topic Submission**: Students can submit project topics for approval
- **Project Tracking**: View project status and evaluation history
- **Progress Monitoring**: Real-time updates on project phases
- **AI Insights**: Personalized performance analysis

### Teacher Dashboard
- **Project Overview**: Visual dashboard with evaluation status
- **Topic Approval**: Review and approve/reject student topics
- **Multi-Phase Evaluation**: Score projects across three defense phases
- **Student Progress**: Track individual and batch performance
- **Batch Analytics**: Download CSV reports and batch statistics

### Advanced Features
- **Smart Filtering**: Hide rejected/abandoned projects after completion
- **Duplicate Detection**: Prevent similar topic submissions
- **Phase-Specific Scoring**: Weighted evaluation system (20+40+20=80 total)
- **Evaluation Averaging**: Multiple evaluations per phase are averaged
- **Auto-Upgrade Logic**: Students progress 4th→6th→8th semester automatically

## Technical Specifications

### Database
- **SQLite**: Lightweight, file-based database
- **Auto-Migration**: Database schema updates automatically
- **Data Integrity**: Foreign key constraints and validation

### Scoring System
- **Proposal Defense**: 0-20 points (25% weight)
- **Midterm Defense**: 0-40 points (50% weight)  
- **Final Defense**: 0-20 points (25% weight)
- **Total Score**: 80 points maximum

### AI Integration
- **Performance Analysis**: Automated feedback generation
- **Status Detection**: Intelligent progress assessment
- **Multi-Phase Summary**: Comprehensive evaluation insights

## Setup Instructions

1. **Start Development Server**:
   ```bash
   php -S localhost:8000
   ```

2. **Database**:
   - SQLite database auto-created on first run
   - Located at `config/database.sqlite`

3. **File Permissions**:
   ```bash
   chmod 755 uploads/
   chmod 644 config/database.sqlite
   ```

## File Structure
```
project-evaluation-system/
├── config/
│   ├── database-sqlite.php
│   └── database.sqlite
├── includes/
│   ├── header.php
│   └── footer.php
├── assets/
│   └── css/style.css
├── uploads/
├── index.php (Teacher Dashboard)
├── student-portal.php (Student Interface)
├── approve-topics.php (Topic Approval)
├── evaluations.php (Multi-Phase Evaluation)
├── batches.php (Batch Management)
├── projects.php (Project Overview)
├── ai-feedback.php (AI Analysis)
└── auto-upgrade-students.php (Semester Progression)
```

## Usage Workflow

### For Students
1. **Access Portal**: Use student ID to login
2. **Submit Topic**: Add project title and description
3. **Track Progress**: Monitor approval and evaluation status
4. **View Feedback**: See AI performance analysis

### For Teachers
1. **Dashboard**: Overview of all projects and status
2. **Approve Topics**: Review and approve student submissions
3. **Evaluate Projects**: Score across three defense phases
4. **Monitor Progress**: Track student and batch performance
5. **Generate Reports**: Download batch analytics

### System Features
- **Auto-Upgrade**: Students automatically progress semesters
- **Smart Filtering**: Clean UI hides irrelevant rejected projects
- **Phase Tracking**: Visual indicators for evaluation progress
- **AI Insights**: Automated performance feedback generation

## API Endpoints
- `student-portal.php?id={student_id}` - Student interface
- `evaluations.php?project_id={id}` - Project evaluation
- `batches.php?semester={n}&batch_year={year}` - Batch view
- `projects.php?student_id={id}` - Student projects

## Recent Updates
- ✅ Phase-specific scoring system (20+40+20=80)
- ✅ Auto-upgrade functionality for semester progression
- ✅ Smart project filtering based on completion status
- ✅ AI performance analysis with phase-specific feedback
- ✅ Advanced search and filtering capabilities
- ✅ Batch management with student search
- ✅ Evaluation averaging for multiple assessments per phase