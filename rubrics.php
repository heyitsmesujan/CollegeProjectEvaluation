<?php
require_once 'config/database-sqlite.php';
include 'includes/header.php';

$action = $_GET['action'] ?? 'list';

if ($_POST && $action === 'add') {
    $criteria = [];
    foreach ($_POST['criteria_name'] as $key => $name) {
        if ($name) {
            $criteria[] = [
                'name' => $name,
                'max_score' => (int)$_POST['criteria_score'][$key],
                'description' => $_POST['criteria_desc'][$key]
            ];
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO rubrics (name, criteria) VALUES (?, ?)");
    $stmt->execute([$_POST['rubric_name'], json_encode($criteria)]);
    
    header('Location: rubrics.php');
    exit;
}

if ($action === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h2>Create Rubric</h2>
        </div>
        
        <form method="POST" id="rubricForm">
            <div class="form-group">
                <label>Rubric Name</label>
                <input type="text" name="rubric_name" class="form-control" required>
            </div>
            
            <div id="criteria-container">
                <h3>Evaluation Criteria</h3>
                <div class="criteria-item">
                    <div class="grid grid-3">
                        <div class="form-group">
                            <label>Criteria Name</label>
                            <input type="text" name="criteria_name[]" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Max Score</label>
                            <input type="number" name="criteria_score[]" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="criteria_desc[]" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="button" onclick="addCriteria()" class="btn">Add Criteria</button>
            <button type="submit" class="btn btn-primary">Create Rubric</button>
            <a href="rubrics.php" class="btn">Cancel</a>
        </form>
    </div>

<?php else:
    $stmt = $pdo->query("SELECT * FROM rubrics ORDER BY created_at DESC");
    $rubrics = $stmt->fetchAll();
?>
    <div class="card">
        <div class="card-header">
            <h2>Rubrics</h2>
            <a href="rubrics.php?action=add" class="btn btn-primary">Create Rubric</a>
        </div>
        
        <?php foreach ($rubrics as $rubric): 
            $criteria = json_decode($rubric['criteria'], true);
        ?>
            <div class="card">
                <h4><?= htmlspecialchars($rubric['name']) ?></h4>
                <div class="grid grid-3">
                    <?php foreach ($criteria as $criterion): ?>
                        <div class="card">
                            <h5><?= htmlspecialchars($criterion['name']) ?></h5>
                            <p><strong>Max Score:</strong> <?= $criterion['max_score'] ?></p>
                            <p><?= htmlspecialchars($criterion['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif;

include 'includes/footer.php'; ?>