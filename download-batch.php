<?php
require_once 'config/database-sqlite.php';

$semester = $_GET['semester'] ?? null;
$batch_year = $_GET['batch_year'] ?? null;

if (!$semester || !$batch_year) {
    header('Location: batches.php');
    exit;
}

// Get students for the batch
$stmt = $pdo->prepare("SELECT student_id, name FROM students WHERE semester = ? AND batch_year = ? ORDER BY name");
$stmt->execute([$semester, $batch_year]);
$students = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="batch_' . $semester . 'th_sem_' . $batch_year . '.csv"');

// Create CSV output
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Student Name', 'Student ID'], ',', '"', '\\');

// Add student data
foreach ($students as $student) {
    fputcsv($output, [$student['name'], $student['student_id']], ',', '"', '\\');
}

fclose($output);
exit;
?>