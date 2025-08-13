<?php
require_once 'config/database-sqlite.php';

function autoUpgradeStudents() {
    global $pdo;
    
    $current_year = date('Y');
    $current_month = date('n'); // 1-12
    
    // Determine current academic semester based on month
    // Jan-May: Spring semester, Jun-Dec: Fall semester
    $academic_semester = $current_month <= 5 ? 'spring' : 'fall';
    
    // Get all students who need upgrading
    $stmt = $pdo->query("
        SELECT id, student_id, name, semester, batch_year, 
               CASE 
                   WHEN semester = '4' THEN '6'
                   WHEN semester = '6' THEN '8'
                   ELSE semester
               END as next_semester,
               CASE 
                   WHEN semester = '4' THEN batch_year
                   WHEN semester = '6' THEN batch_year + 1
                   ELSE batch_year
               END as next_batch_year
        FROM students 
        WHERE semester IN ('4', '6')
    ");
    
    $students = $stmt->fetchAll();
    $upgraded_count = 0;
    
    foreach ($students as $student) {
        // Check if student has completed their current semester project with final approval
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed_projects 
            FROM projects 
            WHERE student_id = ? 
            AND semester = ? 
            AND final_status = 'approved'
        ");
        
        $stmt->execute([$student['id'], $student['semester']]);
        $completed = $stmt->fetch()['completed_projects'];
        
        // Auto-upgrade only if they have final approval
        if ($completed > 0) {
            $stmt = $pdo->prepare("
                UPDATE students 
                SET semester = ?, batch_year = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $student['next_semester'], 
                $student['next_batch_year'], 
                $student['id']
            ]);
            
            $upgraded_count++;
            echo "Upgraded: {$student['name']} ({$student['student_id']}) from {$student['semester']}th to {$student['next_semester']}th semester\n";
        }
    }
    
    return $upgraded_count;
}

// Run auto-upgrade
$upgraded = autoUpgradeStudents();
echo "\nTotal students upgraded: $upgraded\n";
?>