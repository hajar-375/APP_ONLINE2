<?php
session_start();
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$stmt = $pdo->prepare("
    INSERT INTO security_logs 
    (exam_id, attempt_id, incident_type, timestamp) 
    VALUES (?, ?, ?, ?)
");

$stmt->execute([
    $data['examId'],
    $data['attemptId'],
    $data['type'],
    $data['timestamp']
]);