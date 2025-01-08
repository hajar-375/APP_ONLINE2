<?php
session_start();
require_once 'config.php';

if (isset($_FILES['snapshot']) && isset($_POST['examId']) && isset($_POST['attemptId'])) {
    $target_dir = "surveillance/";
    $filename = time() . '_' . $_POST['attemptId'] . '.jpg';
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    move_uploaded_file($_FILES['snapshot']['tmp_name'], $target_dir . $filename);
    
    $stmt = $pdo->prepare("
        INSERT INTO surveillance_snapshots 
        (exam_id, attempt_id, filename, timestamp) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([$_POST['examId'], $_POST['attemptId'], $filename]);
}