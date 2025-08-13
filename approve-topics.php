<?php
require_once 'config/database-sqlite.php';
include 'includes/header.php';

if ($_POST && isset($_POST['action'])) {
    $project_id = $_POST['project_id'];
    $action = $_POST['action'];
    $phase = $_POST['phase'] ?? 'topic';
    
    if ($action === 'approve') {
        if ($phase === 'topic') {
            $stmt = $pdo->prepare("UPDATE projects SET status = 'approved' WHERE id = ?");
            $stmt->execute([$project_id]);
        } elseif ($phase === 'proposal') {
            $stmt = $pdo->prepare("UPDATE projects SET proposal_status = 'approved' WHERE id = ?");
            $stmt->execute([$project_id]);
        } elseif ($phase === 'midterm') {
            $stmt = $pdo->prepare("UPDATE projects SET midterm_status = 'approved' WHERE id = ?");
            $stmt->execute([$project_id]);
        } elseif ($phase === 'final') {
            $stmt = $pdo->prepare("UPDATE projects SET final_status = 'approved' WHERE id = ?");
            $stmt->execute([$project_id]);
            
            // Trigger auto-upgrade when final evaluation is approved
            include_once 'auto-upgrade-students.php';
        }
    } elseif ($action === 'reject') {
        if ($phase === 'topic') {
            $stmt = $pdo->prepare("UPDATE projects SET status = 'rejected' WHERE id = ?");
        } elseif ($phase === 'proposal') {
            $stmt = $pdo->prepare("UPDATE projects SET proposal_status = 'rejected' WHERE id = ?");
        } elseif ($phase === 'midterm') {
            $stmt = $pdo->prepare("UPDATE projects SET midterm_status = 'rejected' WHERE id = ?");
        } elseif ($phase === 'final') {
            $stmt = $pdo->prepare("UPDATE projects SET final_status = 'rejected' WHERE id = ?");
        }
        $stmt->execute([$project_id]);
    }
    
    header('Location: approve-topics.php');
    exit;
}

// Get only topics needing approval
$stmt = $pdo->query("
    SELECT p.*, s.name as student_name, s.student_id 
    FROM projects p 
    JOIN students s ON p.student_id = s.id 
    WHERE p.status = 'pending_approval'
    ORDER BY p.created_at ASC
");
$pending_approvals = $stmt->fetchAll();

// Function to calculate similarity
function calculateSimilarity($topic1, $topic2) {
    $topic1 = strtolower(trim($topic1));
    $topic2 = strtolower(trim($topic2));
    
    similar_text($topic1, $topic2, $percent);
    return round($percent, 1);
}
?>

<div class="card">
    <div class="card-header">
        <h2>Topic Approval</h2>
        <p>Review and approve student project topics</p>
    </div>
    
    <?php if ($pending_approvals): ?>
        <?php foreach ($pending_approvals as $project): ?>
            <?php
            // Only topic approval phase
            $phase = 'topic';
            $phase_name = 'Topic';
            
            // Get abandoned projects for this student
            $stmt_abandoned = $pdo->prepare("
                SELECT name, created_at 
                FROM projects 
                WHERE student_id = (SELECT id FROM students WHERE student_id = ?) AND status = 'abandoned'
                ORDER BY created_at DESC
            ");
            $stmt_abandoned->execute([$project['student_id']]);
            $abandoned_projects = $stmt_abandoned->fetchAll();
            ?>
            <?php
            // Check for similar existing topics
            $similar_topics = [];
            $stmt = $pdo->prepare("SELECT p.name, s.name as student_name FROM projects p JOIN students s ON p.student_id = s.id WHERE p.id != ? AND p.status IN ('approved', 'in_progress', 'completed')");
            $stmt->execute([$project['id']]);
            $existing_topics = $stmt->fetchAll();
            
            foreach ($existing_topics as $existing) {
                $similarity = calculateSimilarity($project['name'], $existing['name']);
                if ($similarity > 60) {
                    $similar_topics[] = ['topic' => $existing, 'similarity' => $similarity];
                }
            }
            ?>
            
            <div class="card" style="margin: 1rem 0; border-left: 4px solid #f59e0b;">
                <?php 
                $clean_name = preg_replace('/\s*\[P\d+\]$/', '', $project['name']);
                ?>
                <h4><?= htmlspecialchars($clean_name) ?></h4>

                <p><strong>Student:</strong> <?= htmlspecialchars($project['student_name']) ?> (<?= $project['student_id'] ?>)</p>
                <p><strong>Description:</strong> <?= htmlspecialchars($project['description']) ?></p>
                <p><strong>Submitted:</strong> <?= date('M j, Y', strtotime($project['created_at'])) ?></p>
                
                <?php if ($abandoned_projects): ?>
                    <div style="background: #fef3c7; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
                        <h5 style="color: #92400e; margin: 0 0 0.5rem 0;">⚠️ Student Previously Abandoned:</h5>
                        <?php foreach ($abandoned_projects as $abandoned): ?>
                            <div style="margin: 0.5rem 0; color: #92400e;">
                                <strong>"<?= htmlspecialchars($abandoned['name']) ?>"</strong> 
                                (Abandoned: <?= date('M j, Y', strtotime($abandoned['created_at'])) ?>)
                            </div>
                        <?php endforeach; ?>
                        <p style="color: #92400e; font-size: 0.9rem; margin: 0.5rem 0 0 0;">Consider discussing commitment and project scope with student.</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($similar_topics): ?>
                    <div style="background: #fef3c7; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
                        <h5 style="color: #92400e; margin: 0 0 0.5rem 0;">⚠️ Similar Topics Found:</h5>
                        <?php foreach ($similar_topics as $similar): ?>
                            <div style="margin: 0.5rem 0;">
                                <strong><?= $similar['similarity'] ?>% similar:</strong> 
                                "<?= htmlspecialchars($similar['topic']['name']) ?>" by <?= htmlspecialchars($similar['topic']['student_name']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="background: #d1fae5; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
                        <span style="color: #065f46;">✅ No similar topics found - Topic appears unique</span>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 1rem;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                        <input type="hidden" name="phase" value="<?= $phase ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success">Approve Topic</button>
                    </form>
                    
                    <form method="POST" style="display: inline; margin-left: 0.5rem;">
                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                        <input type="hidden" name="phase" value="<?= $phase ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger">Reject Topic</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No approvals pending at any phase.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>