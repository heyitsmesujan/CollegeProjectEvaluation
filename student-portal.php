<?php
// Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'config/database-sqlite.php';
require_once 'ai-feedback.php';

$student_login_id = $_GET['id'] ?? $_POST['student_login_id'] ?? null;
$student = null;
$projects = [];

if ($student_login_id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_login_id]);
    $student = $stmt->fetch();
}

if ($_POST && isset($_POST['action']) && $student) {
    if ($_POST['action'] === 'add_topic') {
        // Check for form token to prevent refresh submissions
        $form_token = $_POST['form_token'] ?? '';
        if (empty($form_token)) {
            $error_message = "Invalid form submission. Please try again.";
        } else {
            // Check if this token was already used
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE student_id = ? AND name LIKE ? AND created_at > datetime('now', '-10 seconds')");
            $stmt->execute([$student['id'], '%' . $_POST['topic_title'] . '%']);
            $recent_same_title = $stmt->fetch()['count'];
            
            if ($recent_same_title > 0) {
                $error_message = "This topic was already submitted recently. Please refresh the page.";
            } else {
        // Check if student already has a project
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE student_id = ?");
        $stmt->execute([$student['id']]);
        $project_count = $stmt->fetch()['count'];
        
        // Check if student has active (non-rejected, non-abandoned) project in current semester
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE student_id = ? AND status NOT IN ('rejected', 'abandoned') AND semester = ? AND academic_year = ?");
        $stmt->execute([$student['id'], $student['semester'], date('Y')]);
        $active_project_count = $stmt->fetch()['count'];
        
        // Also check if there's already a pending submission to prevent double-click submissions
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE student_id = ? AND status = 'pending_approval' AND semester = ? AND academic_year = ? AND created_at > datetime('now', '-5 seconds')");
        $stmt->execute([$student['id'], $student['semester'], date('Y')]);
        $recent_submission = $stmt->fetch()['count'];
        
        if ($active_project_count == 0 && $recent_submission == 0) {
            // Generate unique project name with ID
            $project_name = $_POST['topic_title'] . ' [P' . time() . ']';
            
            $stmt = $pdo->prepare("INSERT INTO projects (student_id, name, description, status, proposal_status, midterm_status, final_status, semester, academic_year) VALUES (?, ?, ?, 'pending_approval', 'pending', 'pending', 'pending', ?, ?)");
            $stmt->execute([$student['id'], $project_name, $_POST['topic_description'], $student['semester'], date('Y')]);
            
            // Redirect to prevent form resubmission
            header('Location: student-portal.php?id=' . $student['student_id'] . '&success=topic_submitted');
            exit;
        } elseif ($recent_submission > 0) {
            $error_message = "Please wait - your topic submission is being processed.";
            } else {
                $error_message = "You have already submitted a project topic and cannot add another one.";
            }
        }
    }
    } elseif ($_POST['action'] === 'abandon_project') {
        $project_id = $_POST['project_id'];
        
        // Mark project as abandoned
        $stmt = $pdo->prepare("UPDATE projects SET status = 'abandoned' WHERE id = ? AND student_id = ?");
        $stmt->execute([$project_id, $student['id']]);
        
        // Redirect to prevent form resubmission
        header('Location: student-portal.php?id=' . $student['student_id'] . '&success=project_abandoned');
        exit;
    }
}

// Load projects after form processing
if ($student) {
    // Show projects from current and previous semester only
    $current_year = date('Y');
    $current_semester = $student['semester'];
    
    // Calculate previous semester and year
    $prev_semester = null;
    $prev_year = null;
    
    if ($current_semester == '6') {
        $prev_semester = '4';
        $prev_year = $current_year;
    } elseif ($current_semester == '8') {
        $prev_semester = '6';
        $prev_year = $current_year; // Same year for 6th to 8th progression
        
        // Also show 4th semester projects for 8th semester students
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, e.grade, e.total_score 
            FROM projects p 
            LEFT JOIN evaluations e ON p.id = e.project_id 
            WHERE p.student_id = ? 
            AND (
                (p.semester = ? AND p.academic_year = ?) OR
                (p.semester = ? AND p.academic_year = ? AND p.status != 'abandoned') OR
                (p.semester = '4' AND p.academic_year = ? AND p.status != 'abandoned')
            )
            GROUP BY p.id
            ORDER BY p.academic_year DESC, p.semester DESC, p.created_at DESC
        ");
        $stmt->execute([$student['id'], $current_semester, $current_year, $prev_semester, $prev_year, $current_year]);
        $projects = $stmt->fetchAll();
        
        // Get evaluations for each project
        foreach ($projects as &$project) {
            $stmt = $pdo->prepare("SELECT * FROM evaluations_phases WHERE project_id = ? ORDER BY evaluation_date DESC");
            $stmt->execute([$project['id']]);
            $project['evaluations'] = $stmt->fetchAll();
        }
        
        // Skip the normal query execution below
        goto skip_normal_query;
    }
    
    if ($prev_semester) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, e.grade, e.total_score 
            FROM projects p 
            LEFT JOIN evaluations e ON p.id = e.project_id 
            WHERE p.student_id = ? 
            AND (
                (p.semester = ? AND p.academic_year = ?) OR
                (p.semester = ? AND p.academic_year = ? AND p.status != 'abandoned')
            )
            GROUP BY p.id
            ORDER BY p.academic_year DESC, p.semester DESC, p.created_at DESC
        ");
        $stmt->execute([$student['id'], $current_semester, $current_year, $prev_semester, $prev_year]);
    } else {
        // For 4th semester students, only show current semester
        $stmt = $pdo->prepare("
            SELECT p.*, MAX(e.grade) as grade, MAX(e.total_score) as total_score 
            FROM projects p 
            LEFT JOIN evaluations e ON p.id = e.project_id 
            WHERE p.student_id = ? AND p.semester = ? AND p.academic_year = ?
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$student['id'], $current_semester, $current_year]);
        $stmt->execute([$student['id'], $current_semester, $current_year]);
    }
    
    if (!isset($projects) || empty($projects)) {
        $projects = $stmt->fetchAll();
    }
    
    skip_normal_query:
    
    // Get evaluations for each project (only if not already loaded)
    if (isset($projects) && !empty($projects)) {
        foreach ($projects as &$project) {
            if (!isset($project['evaluations'])) {
                $stmt = $pdo->prepare("SELECT * FROM evaluations_phases WHERE project_id = ? ORDER BY evaluation_date DESC");
                $stmt->execute([$project['id']]);
                $project['evaluations'] = $stmt->fetchAll();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Belleza&family=Alegreya:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-title">Student Portal</h1>
        </div>
    </nav>
    
    <main class="main-content">
        <?php if (!$student): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Student Login</h2>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Enter your Student ID</label>
                        <input type="text" name="student_login_id" class="form-control" placeholder="e.g., STU202441001" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Access Portal</button>
                </form>
                
                <?php if ($student_login_id && !$student): ?>
                    <p style="color: red; margin-top: 1rem;">Invalid Student ID. Please check and try again.</p>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h2>Welcome, <?= htmlspecialchars($student['name']) ?></h2>
                    <p>Student ID: <?= $student['student_id'] ?> | <?= $student['semester'] ?>th Semester (Batch <?= $student['batch_year'] ?>)</p>
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                    <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
                        <?php if ($_GET['success'] === 'topic_submitted'): ?>
                            Project topic submitted successfully! Waiting for teacher approval. You cannot edit this once submitted.
                        <?php elseif ($_GET['success'] === 'project_abandoned'): ?>
                            Project abandoned successfully. You can now submit a new project topic below.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php 
            // Recalculate after potential submission for current semester
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE student_id = ? AND status NOT IN ('rejected', 'abandoned') AND semester = ? AND academic_year = ?");
            $stmt->execute([$student['id'], $student['semester'], date('Y')]);
            $has_active_project = $stmt->fetch()['count'] > 0;
            
            // Check if student has rejected project in current semester (only if no completed project in current semester)
            $stmt = $pdo->prepare("SELECT * FROM projects WHERE student_id = ? AND status = 'rejected' AND semester = ? AND academic_year = ? AND NOT EXISTS (SELECT 1 FROM projects WHERE student_id = ? AND final_status = 'approved' AND semester = ? AND academic_year = ?) ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$student['id'], $student['semester'], date('Y'), $student['id'], $student['semester'], date('Y')]);
            $rejected_project = $stmt->fetch();
            ?>
            
            <?php if (!$has_active_project): ?>
            <?php if ($rejected_project): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
                    <?php 
                    $rejected_display_name = preg_replace('/\s*\[P\d+\]$/', '', $rejected_project['name']);
                    ?>
                    <strong>Previous Topic Rejected:</strong> "<?= htmlspecialchars($rejected_display_name) ?>" (ID: <?= $rejected_project['id'] ?>)<br>
                    You can submit a new topic below.
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3><?= $rejected_project ? 'Resubmit Project Topic for ' . $student['semester'] . 'th Semester' : 'Add Project Topic for ' . $student['semester'] . 'th Semester' ?></h3>
                </div>
                
                <form method="POST" action="student-portal.php?id=<?= $student['student_id'] ?>" onsubmit="return submitOnce(this);">
                    <input type="hidden" name="action" value="add_topic">
                    <input type="hidden" name="form_token" value="<?= uniqid() ?>">
                    <div class="form-group">
                        <label>Project Title</label>
                        <input type="text" name="topic_title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Project Description</label>
                        <textarea name="topic_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Topic</button>
                </form>
                
                <script>
                let formSubmitted = false;
                function submitOnce(form) {
                    if (formSubmitted) {
                        return false;
                    }
                    formSubmitted = true;
                    document.getElementById('submitBtn').disabled = true;
                    document.getElementById('submitBtn').innerHTML = 'Submitting...';
                    return true;
                }
                </script>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>My Projects</h3>
                </div>
                
                <?php if ($projects): ?>
                    <div class="grid grid-2">
                        <?php 
                        // Remove duplicates and filter out rejected projects only from semesters with completed projects
                        $completed_semesters = [];
                        foreach ($projects as $project) {
                            if ($project['final_status'] === 'approved') {
                                $completed_semesters[] = $project['semester'] . '_' . $project['academic_year'];
                            }
                        }
                        
                        $unique_projects = [];
                        $seen_ids = [];
                        foreach ($projects as $project) {
                            if (!in_array($project['id'], $seen_ids)) {
                                $semester_key = $project['semester'] . '_' . $project['academic_year'];
                                // Hide rejected and abandoned projects only from semesters where student completed a project
                                if (($project['status'] !== 'rejected' && $project['status'] !== 'abandoned') || !in_array($semester_key, $completed_semesters)) {
                                    $unique_projects[] = $project;
                                }
                                $seen_ids[] = $project['id'];
                            }
                        }
                        ?>
                        <?php foreach ($unique_projects as $project): ?>
                            <div class="card">
                                <?php 
                                // Extract display name (remove project ID suffix)
                                $display_name = preg_replace('/\s*\[P\d+\]$/', '', $project['name']);
                                ?>
                                <h4><?= htmlspecialchars($display_name) ?></h4>
                                <small style="color: #6b7280;">Project ID: <?= $project['id'] ?></small>
                                <p><?= htmlspecialchars($project['description']) ?></p>
                                <?php if ($project['semester'] != $student['semester']): ?>
                                    <p><strong>Semester:</strong> <?= $project['semester'] ?>th Semester (<?= $project['academic_year'] ?>)</p>
                                <?php endif; ?>
                                <?php 
                                // Determine current project phase status
                                if ($project['status'] === 'rejected') {
                                    $status_display = 'Topic Rejected';
                                } elseif ($project['status'] === 'pending_approval') {
                                    $status_display = 'Topic Pending Approval';
                                } elseif ($project['status'] === 'abandoned') {
                                    $status_display = 'Project Abandoned';
                                } elseif ($project['status'] === 'approved') {
                                    // Check phase approval status
                                    if ($project['final_status'] === 'approved') {
                                        $status_display = 'Project Completed';
                                    } elseif ($project['midterm_status'] === 'approved') {
                                        $status_display = 'Awaiting Final Defense';
                                    } elseif ($project['proposal_status'] === 'approved') {
                                        $status_display = 'Awaiting Midterm Defense';
                                    } else {
                                        $status_display = 'Awaiting Proposal Defense';
                                    }
                                    
                                    // Add semester context for previous semester projects
                                    if ($project['semester'] != $student['semester']) {
                                        $status_display .= ' (Previous Semester)';
                                    }
                                } else {
                                    $status_display = ucfirst(str_replace('_', ' ', $project['status']));
                                }
                                ?>
                                <p><strong>Status:</strong> <span class="status-badge status-<?= $project['status'] ?>"><?= $status_display ?></span></p>
                                
                                <?php if ($project['grade']): ?>
                                    <p><strong>Grade:</strong> <span class="status-badge status-completed"><?= $project['grade'] ?></span></p>
                                    <p><strong>Score:</strong> <?= $project['total_score'] ?></p>
                                <?php elseif ($project['final_status'] === 'approved'): ?>
                                    <p><strong>Status:</strong> <span class="status-badge status-completed">All Evaluations Completed</span></p>
                                <?php else: ?>
                                    <p><em>Evaluation pending</em></p>
                                    <?php if ($project['status'] === 'approved' && $project['semester'] == $student['semester'] && $project['academic_year'] == date('Y')): ?>
                                        <form method="POST" action="student-portal.php?id=<?= $student['student_id'] ?>" style="margin-top: 1rem;">
                                            <input type="hidden" name="action" value="abandon_project">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to abandon this project and start a new topic? This cannot be undone.')">
                                                <i class="fas fa-refresh"></i> Abandon & Start New Topic
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($project['progress_notes']): ?>
                                    <p><strong>Progress Notes:</strong> <?= htmlspecialchars($project['progress_notes']) ?></p>
                                <?php endif; ?>
                                
                                <?php if (isset($project['evaluations']) && $project['evaluations']): ?>
                                    <h5>Evaluation History:</h5>
                                    <?php foreach ($project['evaluations'] as $evaluation): ?>
                                        <div style="background: #f8fafc; padding: 0.5rem; margin: 0.5rem 0; border-radius: 0.25rem;">
                                            <strong><?= ucwords(str_replace('_', ' ', $evaluation['evaluation_phase'])) ?></strong> - <?= $evaluation['evaluation_date'] ? date('M j, Y', strtotime($evaluation['evaluation_date'])) : 'No date' ?><br>
                                            <?php 
                                            $maxScore = ($evaluation['evaluation_phase'] === 'proposal_defense') ? 20 : 
                                                       (($evaluation['evaluation_phase'] === 'midterm_defense') ? 40 : 20);
                                            ?>
                                            Score: <span class="score-display"><?= $evaluation['score'] ?>/<?= $maxScore ?></span><br>
                                            <?php if ($evaluation['feedback']): ?>
                                                Feedback: <?= htmlspecialchars($evaluation['feedback']) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div style="background: #e0f2fe; padding: 0.75rem; margin: 0.5rem 0; border-radius: 0.25rem; border-left: 4px solid #0288d1;">
                                        <strong>🤖 AI Performance Analysis:</strong><br>
                                        <pre style="white-space: pre-wrap; font-family: inherit; margin: 0.5rem 0;"><?= getAIEvaluation($project['id'], $pdo) ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No projects added yet. Add your first project topic above.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>