<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé']);
    exit();
}

// Activer le rapport d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$dbname = 'exam_online';
$username = 'root';
$password = '';
$port = '3306';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $pdo->beginTransaction();
    
    // Debug message
    error_log("Début de la transaction");

    // Vérifier les données POST
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['duration']) || empty($_POST['passing_score']) || empty($_POST['exam_date'])) {
        echo json_encode(['status' => 'error', 'message' => 'Tous les champs sont requis.']);
        exit();
    }

    // Vérifier si l'ID de l'enseignant existe dans la table users
    $teacherId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :teacher_id");
    $stmt->execute([':teacher_id' => $teacherId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => "L'ID de l'enseignant n'existe pas dans la table users."]);
        exit();
    }

    // Insert exam
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $passingScore = $_POST['passing_score'];
    $examDate = $_POST['exam_date'];

    $sql = "INSERT INTO exams (title, description, duration, passing_score, exam_date, teacher_id) VALUES (:title, :description, :duration, :passing_score, :exam_date, :teacher_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':duration' => $duration,
        ':passing_score' => $passingScore,
        ':exam_date' => $examDate,
        ':teacher_id' => $teacherId
    ]);
    
    // Debug message
    error_log("Insertion de l'examen réussie");

    $examId = $pdo->lastInsertId();

    // Process questions
    if (isset($_POST['questions']) && is_array($_POST['questions'])) {
        foreach ($_POST['questions'] as $questionData) {
            // Insert question
            $stmt = $pdo->prepare("
                INSERT INTO questions (
                    exam_id,
                    question_text,
                    question_name,
                    points
                ) VALUES (
                    :exam_id,
                    :question_text,
                    :question_name,
                    :points
                )
            ");

            $stmt->execute([
                'exam_id' => $examId,
                'question_text' => $questionData['text'],
                'question_name' => $questionData['name'],
                'points' => $questionData['points']
            ]);

            $questionId = $pdo->lastInsertId();

            // Process answers based on question type
            if (isset($questionData['answers']) && is_array($questionData['answers'])) {
                foreach ($questionData['answers'] as $index => $answer) {
                    $isCorrect = 0;
                    
                    // For radio buttons (single choice)
                    if (isset($questionData['correct_answer']) && $questionData['correct_answer'] == $index) {
                        $isCorrect = 1;
                    }
                    // For checkboxes (multiple choice)
                    elseif (isset($questionData['correct_answers']) && in_array($index, $questionData['correct_answers'])) {
                        $isCorrect = 1;
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO answers (
                            question_id,
                            answer_text,
                            is_correct
                        ) VALUES (
                            :question_id,
                            :answer_text,
                            :is_correct
                        )
                    ");

                    $stmt->execute([
                        'question_id' => $questionId,
                        'answer_text' => $answer['text'],
                        'is_correct' => $isCorrect
                    ]);
                }
            }
            // For text answers
            elseif (isset($questionData['correct_answer']) && !is_array($questionData['correct_answer'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO answers (
                        question_id,
                        answer_text,
                        is_correct
                    ) VALUES (
                        :question_id,
                        :answer_text,
                        1
                    )
                ");

                $stmt->execute([
                    'question_id' => $questionId,
                    'answer_text' => $questionData['correct_answer']
                ]);
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    // Send success response
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Examen créé avec succès', 'exam_id' => $examId]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur PDO: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur lors de la création de l\'examen: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur inattendue: ' . $e->getMessage()
    ]);
}
