<?php
require_once 'config/database-sqlite.php';
include 'includes/header.php';

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'upgrade_all') {
        include 'auto-upgrade-students.php';
        $success_message = "Auto-upgrade completed for students with final approval!";
    } elseif ($_POST['action'] === 'manual_upgrade' && isset($_POST['student_id'])) {
        $stmt = $pdo->prepare("UPDATE students SET semester = ?, batch_year = ? WHERE id = ?");
        $stmt->execute([$_POST['next_semester'], $_POST['next_batch_year'], $_POST['student_id']]);
        $success_message = "Student manually upgraded to next semester!";
    }
}

// Get students eligible for upgrade
$stmt = $pdo->query("
    SELECT s.*, 
           CASE 
               WHEN s.semester = '4' THEN '6'
               WHEN s.semester = '6' THEN '8'
               ELSE s.semester
           END as next_semester,
           COUNT(p.id) as total_projects,
           COUNT(CASE WHEN p.final_status = 'approved' THEN 1 END) as completed_projects,
           COUNT(CASE WHEN p.final_status != 'approved' AND p.status != 'rejected' THEN 1 END) as incomplete_projects
    FROM students s
    LEFT JOIN projects p ON s.id = p.student_id 
        AND p.semester = s.semester 
    WHERE s.semester IN ('4', '6')
    GROUP BY s.id
    ORDER BY s.semester, s.name
");
$students = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Student Semester Upgrade</h2>
        <p>Automatically upgrade students to next semester</p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
            <?= $success_message ?>
        </div>
    <?php endif; ?>
    
    <div style="margin: 1rem 0;">
        <form method="POST">
            <input type="hidden" name="action" value="upgrade_all">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-arrow-up"></i> Auto-Upgrade Students with Final Approval
            </button>
        </form>
    </div>
    
    <h3>Students Eligible for Upgrade</h3>
    
    <?php if ($students): ?>
        <div class="grid grid-2">
            <?php foreach ($students as $student): ?>
                <div class="card">
                    <h4><?= htmlspecialchars($student['name']) ?></h4>
                    <p><strong>ID:</strong> <?= $student['student_id'] ?></p>
                    <p><strong>Current:</strong> <?= $student['semester'] ?>th Semester (Batch <?= $student['batch_year'] ?>)</p>
                    <p><strong>Next:</strong> <?= $student['next_semester'] ?>th Semester</p>
                    <p><strong>Projects:</strong> <?= $student['completed_projects'] ?> completed, <?= $student['incomplete_projects'] ?> incomplete</p>
                    
                    <?php if ($student['completed_projects'] > 0): ?>
                        <span style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                            ✅ Auto-Upgrade (Final Approved)
                        </span>
                    <?php elseif ($student['incomplete_projects'] > 0): ?>
                        <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                            ⚠️ Manual Upgrade Available
                        </span>
                        <form method="POST" style="margin-top: 0.5rem;">
                            <input type="hidden" name="action" value="manual_upgrade">
                            <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                            <input type="hidden" name="next_semester" value="<?= $student['next_semester'] ?>">
                            <input type="hidden" name="next_batch_year" value="<?= $student['next_semester'] == '6' ? $student['batch_year'] : $student['batch_year'] + 1 ?>">
                            <button type="submit" class="btn btn-warning" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                                Manual Upgrade
                            </button>
                        </form>
                    <?php else: ?>
                        <span style="background: #f3f4f6; color: #6b7280; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                            📝 No Projects Yet
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No students eligible for upgrade at this time.</p>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3>Upgrade Rules</h3>
    </div>
    
    <ul>
        <li><strong>Auto-Upgrade:</strong> Students with final_status = 'approved' upgrade automatically</li>
        <li><strong>Manual Upgrade:</strong> Students with incomplete projects can be manually upgraded</li>
        <li><strong>Previous Projects:</strong> Incomplete projects remain accessible in new semester</li>
        <li><strong>No Status Change:</strong> Previous project status remains unchanged during upgrade</li>
        <li><strong>Multi-Semester View:</strong> Students see current + previous semester projects</li>
    </ul>
</div>

<?php include 'includes/footer.php'; ?>