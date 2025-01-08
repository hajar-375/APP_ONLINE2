<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Accès non autorisé'
    ]);
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'exam_online';
$username = 'root';
$password = '';
$port = '3306';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $username,$password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get available exams (not taken by the student)
    $stmt = $pdo->prepare("
        SELECT e.*, u.name as teacher_name,
               CASE 
                   WHEN se.status IS NULL THEN 'not_started'
                   WHEN se.status = 'in_progress' THEN 'in_progress'
                   ELSE 'completed'
               END as exam_status
        FROM exams e
        JOIN users u ON e.teacher_id = u.id
        LEFT JOIN student_exams se ON e.id = se.exam_id 
            AND se.student_id = ?
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $exams = $stmt->fetchAll();

    $html = '';
    foreach ($exams as $exam) {
        $buttonClass = 'btn-start-exam';
        $buttonText = 'Commencer l\'examen';
        $buttonAction = "onclick=\"startExam({$exam['id']})\"";
        $statusClass = 'status-available';
        $statusText = 'Disponible';

        if ($exam['exam_status'] === 'in_progress') {
            $buttonText = 'Continuer l\'examen';
            $statusClass = 'status-in-progress';
            $statusText = 'En cours';
        } elseif ($exam['exam_status'] === 'completed') {
            $buttonText = 'Voir les résultats';
            $buttonClass = 'btn-view-results';
            $buttonAction = "onclick=\"viewResults({$exam['id']})\"";
            $statusClass = 'status-completed';
            $statusText = 'Terminé';
        }

        $html .= "
            <div class='exam-card'>
                <div class='exam-header'>
                    <h3>" . htmlspecialchars($exam['title']) . "</h3>
                    <span class='exam-status {$statusClass}'>{$statusText}</span>
                </div>
                <p>" . htmlspecialchars($exam['description']) . "</p>
                <div class='exam-details'>
                    <span><i class='fas fa-user'></i> " . htmlspecialchars($exam['teacher_name']) . "</span>
                    <span><i class='fas fa-clock'></i> {$exam['duration']} minutes</span>
                </div>
                <button class='{$buttonClass}' {$buttonAction}>
                    {$buttonText}
                </button>
            </div>
        ";
    }

    if (empty($html)) {
        $html = '<p class="no-exams">Aucun examen disponible pour le moment.</p>';
    }

    echo json_encode([
        'status' => 'success',
        'html' => $html
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 