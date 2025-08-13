<?php
require_once 'config/database-sqlite.php';
require_once 'ai-feedback.php';
include 'includes/header.php';

$action = $_GET['action'] ?? 'list';

if ($_POST) {
    if ($action === 'add') {
        // Add student if new
        $student_name = $_POST['student_name'];
        $student_email = $_POST['student_email'];
        $semester = $_POST['semester'];
        $batch_year = $_POST['batch_year'];
        
        $student_unique_id = 'STU' . $batch_year . $semester . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO students (name, email, semester, batch_year, student_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_name, $student_email, $semester, $batch_year, $student_unique_id]);
        
        $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->execute([$student_email]);
        $student_id = $stmt->fetch()['id'];
        
        // Handle file upload
        $files = '';
        if ($_FILES['project_files']['name'][0]) {
            $uploaded_files = [];
            foreach ($_FILES['project_files']['name'] as $key => $name) {
                if ($name) {
                    $filename = time() . '_' . $name;
                    move_uploaded_file($_FILES['project_files']['tmp_name'][$key], "uploads/$filename");
                    $uploaded_files[] = $filename;
                }
            }
            $files = json_encode($uploaded_files);
        }
        
        $stmt = $pdo->prepare("INSERT INTO projects (student_id, name, description, attendance_records, meeting_times, files) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $_POST['project_name'], $_POST['description'], $_POST['attendance'], $_POST['meetings'], $files]);
        
        header('Location: projects.php');
        exit;
    }
}

if ($action === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h2>Add New Project</h2>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" name="student_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Student Email</label>
                    <input type="email" name="student_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" class="form-control" required>
                        <option value="4">4th Semester</option>
                        <option value="6">6th Semester</option>
                        <option value="8">8th Semester</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Batch Year</label>
                    <input type="number" name="batch_year" class="form-control" min="2020" max="2030" value="<?= date('Y') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Project Name</label>
                <input type="text" name="project_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Attendance Records</label>
                    <textarea name="attendance" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Meeting Times</label>
                    <textarea name="meetings" class="form-control" rows="3"></textarea>
                </div>
            </div>
            
            <div class="form-group">
                <label>Project Files</label>
                <input type="file" name="project_files[]" class="form-control" multiple>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Project</button>
            <a href="projects.php" class="btn">Cancel</a>
        </form>
    </div>

<?php else:
    $student_id = $_GET['student_id'] ?? null;
    
    if ($student_id) {
        // Show ALL projects for specific student (including rejected, abandoned, completed)
        $stmt = $pdo->prepare("SELECT p.*, s.name as student_name, s.email, s.semester as student_semester, s.batch_year, s.student_id, e.grade, e.total_score FROM projects p JOIN students s ON p.student_id = s.id LEFT JOIN evaluations e ON p.id = e.project_id WHERE s.id = ? ORDER BY p.created_at DESC");
        $stmt->execute([$student_id]);
        $projects = $stmt->fetchAll();
        
        // Get student info for header
        $stmt = $pdo->prepare("SELECT name FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student_name = $stmt->fetch()['name'] ?? 'Unknown Student';
    } else {
        // Show all projects
        $stmt = $pdo->query("SELECT p.*, s.name as student_name, s.email, s.semester, s.batch_year, s.student_id FROM projects p JOIN students s ON p.student_id = s.id ORDER BY s.semester, s.batch_year DESC, p.created_at DESC");
        $projects = $stmt->fetchAll();
        $student_name = null;
    }
?>
    <div class="card">
        <div class="card-header">
            <h2><?= $student_name ? 'Projects for ' . htmlspecialchars($student_name) : 'All Projects' ?></h2>
            <div>
                <?php if ($student_name): ?>
                    <a href="projects.php" class="btn">View All Projects</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($projects): ?>
            <div class="grid grid-2">
                <?php foreach ($projects as $project): ?>
                    <?php
                    // Get evaluations for this project
                    $stmt = $pdo->prepare("SELECT * FROM evaluations_phases WHERE project_id = ? ORDER BY evaluation_date DESC");
                    $stmt->execute([$project['id']]);
                    $project['evaluations'] = $stmt->fetchAll();
                    ?>
                    <div class="card">
                        <?php 
                        // Extract display name (remove project ID suffix)
                        $display_name = preg_replace('/\s*\[P\d+\]$/', '', $project['name']);
                        ?>
                        <h4><?= htmlspecialchars($display_name) ?></h4>
                        <?php if (!$student_name): ?>
                            <p><strong>Student:</strong> <?= htmlspecialchars($project['student_name']) ?> (<?= $project['student_id'] ?>)</p>
                        <?php endif; ?>
                        <p><?= htmlspecialchars($project['description']) ?></p>
                        <p><strong>Semester:</strong> <?= $project['semester'] ?>th Semester (<?= $project['batch_year'] ?>)</p>
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
                            
                            // Add semester context for previous semester projects (skip for teacher view)
                            // if ($project['semester'] != ($student['semester'] ?? null)) {
                            //     $status_display .= ' (Previous Semester)';
                            // }
                        } else {
                            $status_display = ucfirst(str_replace('_', ' ', $project['status']));
                        }
                        ?>
                        <p><strong>Status:</strong> <span class="status-badge status-<?= $project['status'] ?>"><?= $status_display ?></span></p>
                        
                        <?php if (isset($project['grade']) && $project['grade']): ?>
                            <p><strong>Grade:</strong> <span class="status-badge status-completed"><?= $project['grade'] ?></span></p>
                            <p><strong>Score:</strong> <?= $project['total_score'] ?></p>
                        <?php elseif ($project['final_status'] === 'approved'): ?>
                            <p><strong>Status:</strong> <span class="status-badge status-completed">All Evaluations Completed</span></p>
                        <?php else: ?>
                            <p><em>Evaluation pending</em></p>
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
                        
                        <div style="margin-top: 1rem;">
                            <a href="evaluations.php?project_id=<?= $project['id'] ?>" class="btn btn-success">Evaluate</a>
                            <a href="update-progress.php?id=<?= $project['id'] ?>" class="btn btn-primary">Update Progress</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No projects found.</p>
        <?php endif; ?>
    </div>
<?php endif;

include 'includes/footer.php'; ?>