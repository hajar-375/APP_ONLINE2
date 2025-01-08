<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher' || !isset($_GET['exam_id'])) {
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

    // Verify teacher owns this exam
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$_GET['exam_id'], $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Examen non trouvé'
        ]);
        exit();
    }

    // Get student results
    $stmt = $pdo->prepare("
        SELECT 
            se.*,
            u.name,
            u.email,
            e.passing_score,
            COUNT(DISTINCT q.id) as total_questions,
            SUM(q.points) as total_points,
            COUNT(DISTINCT sa.id) as answered_questions,
            SUM(sa.points_earned) as earned_points
        FROM student_exams se
        JOIN users u ON se.student_id = u.id
        JOIN exams e ON se.exam_id = e.id
        LEFT JOIN questions q ON e.id = q.exam_id
        LEFT JOIN student_answers sa ON se.id = sa.student_exam_id AND q.id = sa.question_id
        WHERE se.exam_id = ?
        GROUP BY se.id, u.id
        ORDER BY se.created_at DESC
    ");
    $stmt->execute([$_GET['exam_id']]);
    $results = $stmt->fetchAll();

    // Calculate statistics
    $total_students = count($results);
    $passed_students = 0;
    $total_score = 0;

    $formatted_results = [];
    foreach ($results as $result) {
        if ($result['status'] === 'completed') {
            $passed_students++;
        }
        $total_score += $result['score'] ?? 0;

        $formatted_results[] = [
            'attempt_id' => $result['id'],
            'name' => $result['name'],
            'email' => $result['email'],
            'score' => $result['score'] ?? 0,
            'total_points' => $result['total_points'] ?? 0,
            'status' => $result['status'],
            'answered_questions' => $result['answered_questions'],
            'total_questions' => $result['total_questions'],
            'created_at' => $result['created_at']
        ];
    }

    $stats = [
        'total_students' => $total_students,
        'passed_students' => $passed_students,
        'pass_rate' => $total_students > 0 ? round(($passed_students / $total_students) * 100) : 0,
        'average_score' => $total_students > 0 ? round($total_score / $total_students, 2) : 0
    ];

    echo json_encode([
        'status' => 'success',
        'results' => $formatted_results,
        'stats' => $stats
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 