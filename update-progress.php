<?php
require_once 'config/database-sqlite.php';

if ($_POST) {
    $project_id = $_POST['project_id'];
    $status = $_POST['status'];
    $progress_notes = $_POST['progress_notes'];
    
    $stmt = $pdo->prepare("UPDATE projects SET status = ?, progress_notes = ? WHERE id = ?");
    $stmt->execute([$status, $progress_notes, $project_id]);
    
    header('Location: projects.php');
    exit;
}

$project_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT p.*, s.name as student_name FROM projects p JOIN students s ON p.student_id = s.id WHERE p.id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Update Progress: <?= htmlspecialchars($project['name']) ?></h2>
        <p><strong>Student:</strong> <?= htmlspecialchars($project['student_name']) ?></p>
    </div>
    
    <form method="POST">
        <input type="hidden" name="project_id" value="<?= $project_id ?>">
        
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="pending" <?= $project['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" <?= $project['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $project['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Progress Notes</label>
            <textarea name="progress_notes" class="form-control" rows="4"><?= htmlspecialchars($project['progress_notes']) ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Update Progress</button>
        <a href="projects.php" class="btn">Cancel</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>