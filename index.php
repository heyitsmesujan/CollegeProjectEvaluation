<?php
require_once 'config/database-sqlite.php';
include 'includes/header.php';

$search = $_GET['search'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$batch_filter = $_GET['batch'] ?? '';

// Build WHERE conditions
$conditions = ["p.status = 'approved'"];
$params = [];

if ($search) {
    $conditions[] = "(p.name LIKE ? OR s.name LIKE ? OR s.student_id LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($semester_filter) {
    $conditions[] = "p.semester = ?";
    $params[] = $semester_filter;
}

if ($batch_filter) {
    $conditions[] = "p.academic_year = ?";
    $params[] = $batch_filter;
}

$whereClause = implode(' AND ', $conditions);

// Get filtered projects
$stmt = $pdo->prepare("
    SELECT p.*, s.name as student_name, s.student_id,
           COUNT(CASE WHEN ep.evaluation_phase = 'proposal_defense' THEN 1 END) as proposal_done,
           COUNT(CASE WHEN ep.evaluation_phase = 'midterm_defense' THEN 1 END) as midterm_done,
           COUNT(CASE WHEN ep.evaluation_phase = 'final_defense' THEN 1 END) as final_done,
           e.grade, e.total_score,
           (AVG(CASE WHEN ep.evaluation_phase = 'proposal_defense' THEN ep.score END) +
            AVG(CASE WHEN ep.evaluation_phase = 'midterm_defense' THEN ep.score END) +
            AVG(CASE WHEN ep.evaluation_phase = 'final_defense' THEN ep.score END)) as avg_score
    FROM projects p 
    JOIN students s ON p.student_id = s.id 
    LEFT JOIN evaluations_phases ep ON p.id = ep.project_id
    LEFT JOIN evaluations e ON p.id = e.project_id
    WHERE $whereClause
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Get available semesters and batches for filter dropdowns
$stmt = $pdo->query("SELECT DISTINCT semester FROM projects WHERE status = 'approved' ORDER BY semester");
$available_semesters = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT DISTINCT academic_year FROM projects WHERE status = 'approved' ORDER BY academic_year DESC");
$available_batches = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="dashboard-header">
    <div>
        <h1 style="font-family: 'Belleza', sans-serif; font-size: 2.5rem; margin: 0;">Dashboard</h1>
    </div>
    <div style="display: flex; gap: 1rem;">
        <a href="projects.php?action=add" class="btn btn-primary" style="background: #4f46e5; padding: 0.75rem 1.5rem; border-radius: 0.5rem;">
            <i class="fas fa-plus"></i> New Project
        </a>
        <a href="add-student.php" class="btn" style="background: #10b981; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem;">
            <i class="fas fa-user-plus"></i> Add Student
        </a>
        <a href="import-students.php" class="btn" style="background: #6b7280; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem;">
            <i class="fas fa-upload"></i> Import Students
        </a>
    </div>
</div>

<div style="margin: 2rem 0;">
    <h2 style="font-family: 'Belleza', sans-serif; font-size: 2rem; margin-bottom: 0.5rem;">Student Projects</h2>
    <p style="color: #6b7280; margin: 0;">Overview of all ongoing student projects and their evaluation status.</p>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3>Search & Filter Projects</h3>
    </div>
    
    <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
        <div class="form-group" style="flex: 2; min-width: 200px;">
            <label>Search Projects</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Search by project name, student name, or student ID..." 
                   class="form-control">
        </div>
        
        <div class="form-group" style="flex: 1; min-width: 120px;">
            <label>Semester</label>
            <select name="semester" class="form-control">
                <option value="">All Semesters</option>
                <?php foreach ($available_semesters as $sem): ?>
                    <option value="<?= $sem ?>" <?= $semester_filter == $sem ? 'selected' : '' ?>>
                        <?= $sem ?>th Semester
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group" style="flex: 1; min-width: 120px;">
            <label>Batch Year</label>
            <select name="batch" class="form-control">
                <option value="">All Batches</option>
                <?php foreach ($available_batches as $batch): ?>
                    <option value="<?= $batch ?>" <?= $batch_filter == $batch ? 'selected' : '' ?>>
                        <?= $batch ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filter
            </button>
            <?php if ($search || $semester_filter || $batch_filter): ?>
                <a href="index.php" class="btn" style="margin-left: 0.5rem;">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($search || $semester_filter || $batch_filter): ?>
    <p style="color: #6b7280; margin: 1rem 0;">
        Found <?= count($projects) ?> project(s)
        <?php if ($search): ?> matching "<?= htmlspecialchars($search) ?>"<?php endif; ?>
        <?php if ($semester_filter): ?> in <?= $semester_filter ?>th Semester<?php endif; ?>
        <?php if ($batch_filter): ?> from Batch <?= $batch_filter ?><?php endif; ?>
    </p>
<?php endif; ?>

<?php if ($projects): ?>
    <div class="grid grid-2" style="gap: 1.5rem;">
        <?php foreach ($projects as $project): ?>
            <div class="project-card">
                <div class="project-header">
                    <?php 
                    $dashboard_display_name = preg_replace('/\s*\[P\d+\]$/', '', $project['name']);
                    ?>
                    <h3><?= htmlspecialchars($dashboard_display_name) ?></h3>
                    <small style="color: #6b7280;">Project ID: <?= $project['id'] ?></small>
                    <p class="student-name"><?= htmlspecialchars($project['student_name']) ?></p>
                </div>
                
                <div class="evaluation-status">
                    <div class="status-item">
                        <i class="fas fa-<?= $project['proposal_done'] ? 'check-circle' : 'circle' ?>" style="color: <?= $project['proposal_done'] ? '#10b981' : '#d1d5db' ?>;"></i>
                        <span>Proposal</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-<?= $project['midterm_done'] ? 'check-circle' : 'circle' ?>" style="color: <?= $project['midterm_done'] ? '#10b981' : '#d1d5db' ?>;"></i>
                        <span>Midterm</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-<?= $project['final_done'] ? 'check-circle' : 'circle' ?>" style="color: <?= $project['final_done'] ? '#10b981' : '#d1d5db' ?>;"></i>
                        <span>Final</span>
                    </div>
                </div>
                
                <div class="project-grade">
                    <?php if ($project['grade']): ?>
                        <div class="grade-info">
                            <span class="grade">Grade: <?= $project['grade'] ?></span>
                            <span class="score"><?= round($project['total_score']) ?>/100</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= ($project['total_score']) ?>%;"></div>
                        </div>
                    <?php elseif ($project['final_done']): ?>
                        <div class="grade-info">
                            <span class="grade">Project Completed</span>
                            <span class="score"><?= round($project['avg_score']) ?>/80</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= ($project['avg_score']/80)*100 ?>%;"></div>
                        </div>
                    <?php else: ?>
                        <div class="awaiting-evaluation">
                            <span>Awaiting Evaluations</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button class="view-project-btn" onclick="location.href='evaluations.php?action=add&project_id=<?= $project['id'] ?>'">
                    Evaluate Project
                </button>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <p>No projects found. <a href="import-students.php">Import students</a> to get started.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>