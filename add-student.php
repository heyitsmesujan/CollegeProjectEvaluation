<?php
require_once 'config/database-sqlite.php';
include 'includes/header.php';

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $semester = $_POST['semester'];
    $batch_year = $_POST['batch_year'];
    
    if ($name) {
        $student_id = 'STU' . $batch_year . $semester . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
        
        try {
            // Handle empty email
            $email_value = empty($email) ? null : $email;
            
            $stmt = $pdo->prepare("INSERT INTO students (name, email, semester, batch_year, student_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email_value, $semester, $batch_year, $student_id]);
            $success_message = "Student added successfully! Student ID: " . $student_id;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $error_message = "Error adding student. Email or Student ID already exists.";
            } else {
                $error_message = "Error adding student: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "Name is required.";
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Add Individual Student</h2>
        <a href="index.php" class="btn">Back to Dashboard</a>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
            <?= $success_message ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
            <?= $error_message ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="action" value="add_student">
        
        <div class="form-group">
            <label>Student Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Email (Optional)</label>
            <input type="email" name="email" class="form-control">
        </div>
        
        <div class="grid grid-2">
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
        
        <button type="submit" class="btn btn-primary">Add Student</button>
        <a href="index.php" class="btn">Cancel</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>