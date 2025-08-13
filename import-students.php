<?php
require_once 'config/database-sqlite.php';
include 'includes/header.php';

if ($_POST && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    $handle = fopen($file, 'r');
    
    $imported = 0;
    $student_ids = [];
    $semester = $_POST['semester'];
    $batch_year = $_POST['batch_year'];
    
    // Skip header row
    fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== FALSE) {
        $name = trim($data[0]);
        $email = trim($data[1]);
        
        if ($name && $email) {
            $student_id = 'STU' . $batch_year . $semester . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO students (name, email, semester, batch_year, student_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $semester, $batch_year, $student_id]);
                $student_ids[] = ['name' => $name, 'email' => $email, 'student_id' => $student_id];
                $imported++;
            } catch (Exception $e) {
                // Skip duplicates
            }
        }
    }
    fclose($handle);
}
?>

<div class="card">
    <div class="card-header">
        <h2>Import Students from Excel</h2>
    </div>
    
    <?php if (!isset($_POST['excel_file'])): ?>
        <form method="POST" enctype="multipart/form-data">
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
            
            <div class="form-group">
                <label>Excel File (CSV format)</label>
                <input type="file" name="excel_file" class="form-control" accept=".csv" required>
                <small>Format: Name, Email (only)</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Import Students</button>
            <a href="index.php" class="btn">Cancel</a>
        </form>
        
        <div style="margin-top: 2rem;">
            <h4>CSV Format Example:</h4>
            <pre style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem;">
Name,Email
John Doe,john@email.com
Jane Smith,jane@email.com</pre>
        </div>
    <?php else: ?>
        <h3>Import Complete!</h3>
        <p><strong><?= $imported ?></strong> students imported successfully.</p>
        
        <div class="card">
            <div class="card-header">
                <h4>Generated Student IDs</h4>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f8fafc;">
                    <th style="padding: 0.5rem; border: 1px solid #ddd;">Name</th>
                    <th style="padding: 0.5rem; border: 1px solid #ddd;">Email</th>
                    <th style="padding: 0.5rem; border: 1px solid #ddd;">Student ID</th>
                </tr>
                <?php foreach ($student_ids as $student): ?>
                    <tr>
                        <td style="padding: 0.5rem; border: 1px solid #ddd;"><?= htmlspecialchars($student['name']) ?></td>
                        <td style="padding: 0.5rem; border: 1px solid #ddd;"><?= htmlspecialchars($student['email']) ?></td>
                        <td style="padding: 0.5rem; border: 1px solid #ddd; font-weight: bold;"><?= $student['student_id'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div style="margin-top: 1rem;">
            <a href="import-students.php" class="btn btn-primary">Import More</a>
            <a href="index.php" class="btn">Back to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>