<?php
require_once 'config/database-sqlite.php';
include 'includes/header.php';

$semester = $_GET['semester'] ?? null;
$batch_year = $_GET['batch_year'] ?? null;
$search = $_GET['search'] ?? '';

// Check if search is for student name (contains letters)
$isStudentSearch = $search && preg_match('/[a-zA-Z]/', $search) && !is_numeric($search);

if ($isStudentSearch) {
    // Search for students directly
    $stmt = $pdo->prepare("SELECT s.*, COUNT(p.id) as project_count FROM students s LEFT JOIN projects p ON s.id = p.student_id WHERE s.name LIKE ? GROUP BY s.id ORDER BY s.name");
    $stmt->execute(['%' . $search . '%']);
    $students = $stmt->fetchAll();
    $batches = [];
} else {
    // Build WHERE conditions for batches
    $conditions = [];
    $params = [];
    
    if ($search) {
        $conditions[] = "batch_year LIKE ?";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
    }
    
    $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get filtered batches
    $stmt = $pdo->prepare("SELECT semester, batch_year, COUNT(*) as student_count FROM students $whereClause GROUP BY semester, batch_year ORDER BY semester, batch_year DESC");
    $stmt->execute($params);
    $batches = $stmt->fetchAll();
    $students = [];
}

if ($semester && $batch_year):
    // Build WHERE conditions for students
    $student_conditions = ["s.semester = ?", "s.batch_year = ?"];
    $student_params = [$semester, $batch_year];
    
    if ($search) {
        $student_conditions[] = "(s.name LIKE ? OR s.student_id LIKE ? OR s.email LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $student_params[] = $searchTerm;
        $student_params[] = $searchTerm;
        $student_params[] = $searchTerm;
    }
    
    $student_whereClause = 'WHERE ' . implode(' AND ', $student_conditions);
    
    $stmt = $pdo->prepare("SELECT s.*, COUNT(p.id) as project_count FROM students s LEFT JOIN projects p ON s.id = p.student_id $student_whereClause GROUP BY s.id ORDER BY s.name");
    $stmt->execute($student_params);
    $students = $stmt->fetchAll();
?>
    <div class="card">
        <div class="card-header">
            <h2><?= $semester ?>th Semester - Batch <?= $batch_year ?></h2>
            <div>
                <a href="download-batch.php?semester=<?= $semester ?>&batch_year=<?= $batch_year ?>" class="btn btn-success">
                    <i class="fas fa-download"></i> Download CSV
                </a>
                <a href="batches.php" class="btn">Back to Batches</a>
            </div>
        </div>
        
        <div class="grid grid-2">
            <?php foreach ($students as $student): ?>
                <div class="card">
                    <h4><?= htmlspecialchars($student['name']) ?></h4>
                    <p><strong>Email:</strong> <?= htmlspecialchars($student['email'] ?? '') ?></p>
                    <p><strong>Projects:</strong> <?= $student['project_count'] ?></p>
                    <div style="margin-top: 1rem;">
                        <a href="projects.php?student_id=<?= $student['id'] ?>" class="btn btn-primary">View Projects</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php else: ?>
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h3>Search & Filter Batches</h3>
        </div>
        
        <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
            <div class="form-group" style="flex: 2; min-width: 200px;">
                <label>Search Batches</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search by batch year or student name..." 
                       class="form-control">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <?php if ($search): ?>
                    <a href="batches.php" class="btn" style="margin-left: 0.5rem;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Student Batches</h2>
        </div>
        
        <?php if ($search && $isStudentSearch): ?>
            <p style="color: #6b7280; margin: 1rem 0;">
                Found <?= count($students) ?> student(s) matching "<?= htmlspecialchars($search) ?>"
            </p>
            
            <div class="grid grid-2">
                <?php foreach ($students as $student): ?>
                    <div class="card">
                        <h4><?= htmlspecialchars($student['name']) ?></h4>
                        <p><strong>Email:</strong> <?= htmlspecialchars($student['email'] ?? '') ?></p>
                        <p><strong>Semester:</strong> <?= $student['semester'] ?>th (Batch <?= $student['batch_year'] ?>)</p>
                        <p><strong>Projects:</strong> <?= $student['project_count'] ?></p>
                        <div style="margin-top: 1rem;">
                            <a href="projects.php?student_id=<?= $student['id'] ?>" class="btn btn-primary">View Projects</a>
                            <a href="batches.php?semester=<?= $student['semester'] ?>&batch_year=<?= $student['batch_year'] ?>" class="btn" style="margin-left: 0.5rem;">View Batch</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php if ($search): ?>
                <p style="color: #6b7280; margin: 1rem 0;">
                    Found <?= count($batches) ?> batch(es) matching "<?= htmlspecialchars($search) ?>"
                </p>
            <?php endif; ?>
            
            <div class="grid grid-3">
                <?php foreach ($batches as $batch): ?>
                    <div class="card">
                        <h4><?= $batch['semester'] ?>th Semester</h4>
                        <p><strong>Batch:</strong> <?= $batch['batch_year'] ?></p>
                        <p><strong>Students:</strong> <?= $batch['student_count'] ?></p>
                        <div style="margin-top: 1rem;">
                            <a href="batches.php?semester=<?= $batch['semester'] ?>&batch_year=<?= $batch['batch_year'] ?>" class="btn btn-primary">View Students</a>
                            <a href="download-batch.php?semester=<?= $batch['semester'] ?>&batch_year=<?= $batch['batch_year'] ?>" class="btn btn-success" style="margin-left: 0.5rem;">
                                <i class="fas fa-download"></i> CSV
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif;

include 'includes/footer.php'; ?>