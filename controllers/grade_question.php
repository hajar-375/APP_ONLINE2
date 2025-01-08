<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher' || !isset($_POST['question_id'])) {
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

    $question_id = $_POST['question_id'];
    $student_exam_id = $_POST['student_exam_id'];
    $points = min((int)$_POST['points'], 100); // Prevent unreasonable points

    // Verify teacher owns this exam
    $stmt = $pdo->prepare("
        SELECT e.* FROM exams e
        JOIN questions q ON e.id = q.exam_id
        JOIN student_exams se ON e.id = se.exam_id
        WHERE q.id = ? AND se.id = ? AND e.teacher_id = ?
    ");
    $stmt->execute([$question_id, $student_exam_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Question non trouvée ou non autorisée'
        ]);
        exit();
    }

    // Start transaction
    $pdo->beginTransaction();

    // Update points for this question
    $stmt = $pdo->prepare("
        UPDATE student_answers 
        SET points_earned = ?, is_correct = ?
        WHERE student_exam_id = ? AND question_id = ?
    ");
    $stmt->execute([$points, ($points > 0), $student_exam_id, $question_id]);

    // Recalculate total score
    $stmt = $pdo->prepare("
        SELECT 
            SUM(COALESCE(sa.points_earned, 0)) as total_score,
            e.passing_score
        FROM student_exams se
        JOIN exams e ON se.exam_id = e.id
        LEFT JOIN student_answers sa ON se.id = sa.student_exam_id
        WHERE se.id = ?
        GROUP BY se.id, e.passing_score
    ");
    $stmt->execute([$student_exam_id]);
    $result = $stmt->fetch();
    $total_score = $result['total_score'];
    $passing_score = $result['passing_score'];

    // Update exam status
    $status = $total_score >= $passing_score ? 'completed' : 'failed';
    $stmt = $pdo->prepare("
        UPDATE student_exams 
        SET score = ?, status = ?
        WHERE id = ?
    ");
    $stmt->execute([$total_score, $status, $student_exam_id]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Note enregistrée avec succès',
        'total_score' => $total_score,
        'passing_score' => $passing_score
    ]);

} catch(PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 