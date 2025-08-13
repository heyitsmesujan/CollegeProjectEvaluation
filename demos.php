<?php
require_once 'config/database-sqlite.php';
include 'includes/header.php';

$action = $_GET['action'] ?? 'list';
$project_id = $_GET['project_id'] ?? null;

if ($_POST && $action === 'add') {
    $stmt = $pdo->prepare("INSERT INTO demos (project_id, demo_type, demo_date, feedback, score) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['project_id'], $_POST['demo_type'], $_POST['demo_date'], $_POST['feedback'], $_POST['score']]);
    
    header('Location: demos.php');
    exit;
}

if ($action === 'add'):
    $stmt = $pdo->query("SELECT p.*, s.name as student_name FROM projects p JOIN students s ON p.student_id = s.id");
    $projects = $stmt->fetchAll();
?>
    <div class="card">
        <div class="card-header">
            <h2>Add Demo Evaluation</h2>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Select Project</label>
                <select name="project_id" class="form-control" required>
                    <option value="">Choose project...</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>" <?= $project_id == $project['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($project['name']) ?> - <?= htmlspecialchars($project['student_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Demo Type</label>
                    <select name="demo_type" class="form-control" required>
                        <option value="regular">Regular Demo</option>
                        <option value="proposal_defense">Proposal Defense</option>
                        <option value="midterm_defense">Midterm Defense</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Demo Date</label>
                    <input type="date" name="demo_date" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Score (0-100)</label>
                <input type="number" name="score" class="form-control" min="0" max="100" step="0.1" required>
            </div>
            
            <div class="form-group">
                <label>Feedback</label>
                <textarea name="feedback" class="form-control" rows="4"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Demo</button>
            <a href="demos.php" class="btn">Cancel</a>
        </form>
    </div>

<?php else:
    $stmt = $pdo->query("SELECT d.*, p.name as project_name, s.name as student_name FROM demos d JOIN projects p ON d.project_id = p.id JOIN students s ON p.student_id = s.id ORDER BY d.created_at DESC");
    $demos = $stmt->fetchAll();
?>
    <div class="card">
        <div class="card-header">
            <h2>Demo Evaluations</h2>
            <a href="demos.php?action=add" class="btn btn-primary">Add Demo</a>
        </div>
        
        <div class="grid grid-2">
            <?php foreach ($demos as $demo): ?>
                <div class="card">
                    <h4><?= htmlspecialchars($demo['project_name']) ?></h4>
                    <p><strong>Student:</strong> <?= htmlspecialchars($demo['student_name']) ?></p>
                    <p><strong>Type:</strong> <?= ucwords(str_replace('_', ' ', $demo['demo_type'])) ?></p>
                    <p><strong>Date:</strong> <?= date('M j, Y', strtotime($demo['demo_date'])) ?></p>
                    <p><strong>Score:</strong> <span class="score-display"><?= $demo['score'] ?>/100</span></p>
                    <p><?= htmlspecialchars($demo['feedback']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif;

include 'includes/footer.php'; ?>