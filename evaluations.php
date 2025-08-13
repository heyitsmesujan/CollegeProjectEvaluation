<?php
require_once 'config/database-sqlite.php';
include 'includes/header.php';

$project_id = $_GET['project_id'] ?? null;
$action = $_GET['action'] ?? 'list';

if ($_POST && $action === 'submit') {
    $stmt = $pdo->prepare("INSERT INTO evaluations_phases (project_id, evaluation_phase, evaluation_date, feedback, score) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['project_id'], $_POST['evaluation_phase'], $_POST['evaluation_date'], $_POST['feedback'], $_POST['score']]);
    
    // Handle phase approval
    if (isset($_POST['approve_phase']) && $_POST['approve_phase'] == '1') {
        $phase = $_POST['evaluation_phase'];
        if ($phase === 'proposal_defense') {
            $stmt = $pdo->prepare("UPDATE projects SET proposal_status = 'approved' WHERE id = ?");
            $stmt->execute([$_POST['project_id']]);
        } elseif ($phase === 'midterm_defense') {
            $stmt = $pdo->prepare("UPDATE projects SET midterm_status = 'approved' WHERE id = ?");
            $stmt->execute([$_POST['project_id']]);
        } elseif ($phase === 'final_defense') {
            $stmt = $pdo->prepare("UPDATE projects SET final_status = 'approved' WHERE id = ?");
            $stmt->execute([$_POST['project_id']]);
        }
    }
    
    header('Location: evaluations.php?action=add&project_id=' . $_POST['project_id']);
    exit;
}

if ($action === 'add' && $project_id):
    $stmt = $pdo->prepare("SELECT p.*, s.name as student_name, s.student_id FROM projects p JOIN students s ON p.student_id = s.id WHERE p.id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    // Get existing evaluations
    $stmt = $pdo->prepare("SELECT * FROM evaluations_phases WHERE project_id = ? ORDER BY evaluation_date DESC");
    $stmt->execute([$project_id]);
    $existing_evaluations = $stmt->fetchAll();
?>
    <div class="card">
        <div class="card-header">
            <h2>Project Evaluation: <?= htmlspecialchars($project['name']) ?></h2>
            <p><strong>Student:</strong> <?= htmlspecialchars($project['student_name']) ?> (<?= $project['student_id'] ?>)</p>
            <p><strong>Description:</strong> <?= htmlspecialchars($project['description']) ?></p>
        </div>
        
        <?php if ($existing_evaluations): ?>
            <h3>Evaluation History</h3>
            <div class="grid grid-2">
                <?php foreach ($existing_evaluations as $eval): ?>
                    <div class="card" style="margin: 0.5rem 0;">
                        <h4><?= ucwords(str_replace('_', ' ', $eval['evaluation_phase'])) ?></h4>
                        <p><strong>Date:</strong> <?= date('M j, Y', strtotime($eval['evaluation_date'])) ?></p>
                        <?php 
                        $maxScore = ($eval['evaluation_phase'] === 'proposal_defense') ? 20 : 
                                   (($eval['evaluation_phase'] === 'midterm_defense') ? 40 : 20);
                        ?>
                        <p><strong>Score:</strong> <?= $eval['score'] ?>/<?= $maxScore ?></p>
                        <?php if ($eval['feedback']): ?>
                            <p><strong>Feedback:</strong> <?= htmlspecialchars($eval['feedback']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($project['final_status'] !== 'approved'): ?>
            <h3>Add New Evaluation</h3>
            <form method="POST" action="evaluations.php?action=submit">
                <input type="hidden" name="project_id" value="<?= $project_id ?>">
                

                
                <div class="form-group">
                    <label>Evaluation Date</label>
                    <input type="date" name="evaluation_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Score</label>
                    <select name="evaluation_phase" id="phaseSelect" class="form-control" required onchange="updateScoreRange()">
                        <option value="">Select phase...</option>
                        <option value="proposal_defense">Proposal Defense (0-20)</option>
                        <option value="midterm_defense">Midterm Defense (0-40)</option>
                        <option value="final_defense">Final Defense (0-20)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label id="scoreLabel">Score</label>
                    <input type="number" name="score" id="scoreInput" class="form-control" min="0" max="100" required>
                </div>
                
                <script>
                function updateScoreRange() {
                    const phase = document.getElementById('phaseSelect').value;
                    const scoreInput = document.getElementById('scoreInput');
                    const scoreLabel = document.getElementById('scoreLabel');
                    
                    if (phase === 'proposal_defense') {
                        scoreInput.max = 20;
                        scoreLabel.textContent = 'Score (0-20)';
                    } else if (phase === 'midterm_defense') {
                        scoreInput.max = 40;
                        scoreLabel.textContent = 'Score (0-40)';
                    } else if (phase === 'final_defense') {
                        scoreInput.max = 20;
                        scoreLabel.textContent = 'Score (0-20)';
                    } else {
                        scoreInput.max = 100;
                        scoreLabel.textContent = 'Score';
                    }
                    scoreInput.value = '';
                }
                </script>
                
                <div class="form-group">
                    <label>Feedback</label>
                    <textarea name="feedback" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="approve_phase" value="1"> 
                        Approve this phase (allows student to proceed to next phase)
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Evaluation</button>
                <a href="index.php" class="btn">Back to Dashboard</a>
            </form>
        <?php else: ?>
            <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
                <h3>✅ Project Evaluation Complete</h3>
                <p>All evaluation phases have been completed and approved. No further evaluations can be added.</p>
                <a href="index.php" class="btn btn-success">Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>

<?php else:
    $search = $_GET['search'] ?? '';
    
    // Get all evaluation phases with project and student info
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT ep.*, p.name as project_name, s.name as student_name, s.student_id,
                   p.semester, p.academic_year
            FROM evaluations_phases ep 
            JOIN projects p ON ep.project_id = p.id 
            JOIN students s ON p.student_id = s.id 
            WHERE p.name LIKE ? OR s.name LIKE ? OR s.student_id LIKE ?
            ORDER BY ep.evaluation_date DESC
        ");
        $searchTerm = '%' . $search . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmt = $pdo->query("
            SELECT ep.*, p.name as project_name, s.name as student_name, s.student_id,
                   p.semester, p.academic_year
            FROM evaluations_phases ep 
            JOIN projects p ON ep.project_id = p.id 
            JOIN students s ON p.student_id = s.id 
            ORDER BY ep.evaluation_date DESC
        ");
    }
    $evaluations = $stmt->fetchAll();
?>
    <div class="card">
        <div class="card-header">
            <h2>All Evaluations</h2>
            <p>Complete history of all student project evaluations</p>
        </div>
        
        <div style="margin: 1rem 0;">
            <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search by project name, student name, or student ID..." 
                       class="form-control" style="flex: 1;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="evaluations.php" class="btn">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if ($search && !$evaluations): ?>
            <p>No evaluations found for "<?= htmlspecialchars($search) ?>". <a href="evaluations.php">Show all evaluations</a></p>
        <?php elseif ($evaluations): ?>
            <?php if ($search): ?>
                <p style="color: #6b7280; margin: 1rem 0;">Found <?= count($evaluations) ?> evaluation(s) for "<?= htmlspecialchars($search) ?>"</p>
            <?php endif; ?>
            <div class="grid grid-2">
                <?php foreach ($evaluations as $evaluation): ?>
                    <div class="card">
                        <?php 
                        $eval_display_name = preg_replace('/\s*\[P\d+\]$/', '', $evaluation['project_name']);
                        ?>
                        <h4><?= htmlspecialchars($eval_display_name) ?></h4>
                        <small style="color: #6b7280;">Project ID: <?= $evaluation['project_id'] ?></small>
                        <p><strong>Student:</strong> <?= htmlspecialchars($evaluation['student_name']) ?> (<?= $evaluation['student_id'] ?>)</p>
                        <p><strong>Phase:</strong> <?= ucwords(str_replace('_', ' ', $evaluation['evaluation_phase'])) ?></p>
                        <p><strong>Semester:</strong> <?= $evaluation['semester'] ?>th Semester (<?= $evaluation['academic_year'] ?>)</p>
                        <?php 
                        $maxScore = ($evaluation['evaluation_phase'] === 'proposal_defense') ? 20 : 
                                   (($evaluation['evaluation_phase'] === 'midterm_defense') ? 40 : 20);
                        ?>
                        <p><strong>Score:</strong> <span class="score-display"><?= $evaluation['score'] ?>/<?= $maxScore ?></span></p>
                        <?php if ($evaluation['feedback']): ?>
                            <p><strong>Feedback:</strong> <?= htmlspecialchars($evaluation['feedback']) ?></p>
                        <?php endif; ?>
                        <small>Evaluated: <?= date('M j, Y', strtotime($evaluation['evaluation_date'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No evaluations found. Start evaluating projects from the <a href="index.php">Dashboard</a>.</p>
        <?php endif; ?>
    </div>
<?php endif;

include 'includes/footer.php'; ?>