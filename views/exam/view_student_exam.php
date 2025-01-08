<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher' || !isset($_GET['id'])) {
    header('Location: dashboard.php');
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

    // Get student exam details
    $stmt = $pdo->prepare("
        SELECT 
            se.*,
            e.title as exam_title,
            e.passing_score,
            u.name as student_name,
            u.email as student_email
        FROM student_exams se
        JOIN exams e ON se.exam_id = e.id
        JOIN users u ON se.student_id = u.id
        WHERE se.id = ? AND e.teacher_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $exam_attempt = $stmt->fetch();

    if (!$exam_attempt) {
        header('Location: view_results.php');
        exit();
    }

    // Get all questions and answers
    $stmt = $pdo->prepare("
        SELECT 
            q.*,
            sa.text_answer,
            sa.answer_id,
            sa.is_correct,
            sa.points_earned
        FROM questions q
        LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.student_exam_id = ?
        WHERE q.exam_id = ?
        ORDER BY q.id
    ");
    $stmt->execute([$exam_attempt['id'], $exam_attempt['exam_id']]);
    $questions = $stmt->fetchAll();

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Examen - <?php echo htmlspecialchars($exam_attempt['student_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .student-exam-details {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .exam-header {
            background: var(--card-background);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            background: rgba(33, 150, 243, 0.1);
            padding: 1rem;
            border-radius: 8px;
        }

        .question-card {
            background: var(--card-background);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--shadow-color);
        }

        .points {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .answer {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(33, 150, 243, 0.05);
            border-radius: 8px;
        }

        .answer.correct {
            border-left: 4px solid var(--success-color);
        }

        .answer.incorrect {
            border-left: 4px solid var(--danger-color);
        }

        .grading-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--shadow-color);
        }

        .grading-form input[type="number"] {
            width: 100px;
            padding: 0.5rem;
            border: 2px solid var(--input-border);
            border-radius: 5px;
            margin-right: 1rem;
        }

        .btn-grade {
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-grade:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
        }

        .feedback {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 5px;
        }

        .feedback.success {
            background: var(--success-color);
            color: white;
        }

        .feedback.error {
            background: var(--danger-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="student-exam-details">
        <div class="exam-header">
            <h1><?php echo htmlspecialchars($exam_attempt['exam_title']); ?></h1>
            <div class="student-info">
                <div class="info-item">
                    <strong>Étudiant:</strong>
                    <div><?php echo htmlspecialchars($exam_attempt['student_name']); ?></div>
                </div>
                <div class="info-item">
                    <strong>Email:</strong>
                    <div><?php echo htmlspecialchars($exam_attempt['student_email']); ?></div>
                </div>
                <div class="info-item">
                    <strong>Score:</strong>
                    <div><?php echo $exam_attempt['score'] ?? 'Non noté'; ?>/<?php echo $exam_attempt['passing_score']; ?></div>
                </div>
                <div class="info-item">
                    <strong>Statut:</strong>
                    <div><?php echo ucfirst($exam_attempt['status']); ?></div>
                </div>
            </div>
        </div>

        <?php foreach ($questions as $question): ?>
            <div class="question-card">
                <div class="question-header">
                    <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
                    <span class="points">Points: <?php echo $question['points']; ?></span>
                </div>

                <?php if ($question['question_type'] === 'text'): ?>
                    <div class="answer">
                        <strong>Réponse de l'étudiant:</strong>
                        <p><?php echo nl2br(htmlspecialchars($question['text_answer'] ?? 'Pas de réponse')); ?></p>
                    </div>

                    <form class="grading-form" id="grade-form-<?php echo $question['id']; ?>">
                        <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                        <input type="hidden" name="student_exam_id" value="<?php echo $exam_attempt['id']; ?>">
                        <label>
                            Points attribués:
                            <input type="number" name="points" 
                                   min="0" max="<?php echo $question['points']; ?>" 
                                   value="<?php echo $question['points_earned'] ?? 0; ?>">
                            /<?php echo $question['points']; ?>
                        </label>
                        <button type="submit" class="btn-grade">Enregistrer la note</button>
                        <div class="feedback" id="feedback-<?php echo $question['id']; ?>"></div>
                    </form>

                <?php else: ?>
                    <?php
                    // Get all possible answers
                    $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
                    $stmt->execute([$question['id']]);
                    $answers = $stmt->fetchAll();

                    foreach ($answers as $answer):
                        $is_selected = $question['answer_id'] == $answer['id'];
                        $class = $is_selected ? ($answer['is_correct'] ? 'correct' : 'incorrect') : '';
                    ?>
                        <div class="answer <?php echo $class; ?>">
                            <label>
                                <input type="<?php echo $question['question_type'] === 'multiple_choice' ? 'radio' : 'checkbox'; ?>"
                                       disabled
                                       <?php echo $is_selected ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($answer['answer_text']); ?>
                            </label>
                            <?php if ($is_selected && $answer['is_correct']): ?>
                                <i class="fas fa-check" style="color: var(--success-color);"></i>
                            <?php elseif ($is_selected && !$answer['is_correct']): ?>
                                <i class="fas fa-times" style="color: var(--danger-color);"></i>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Handle grading form submissions
        document.querySelectorAll('.grading-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const questionId = formData.get('question_id');
                const feedbackDiv = document.getElementById(`feedback-${questionId}`);
                
                fetch('grade_question.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    feedbackDiv.textContent = data.message;
                    feedbackDiv.className = `feedback ${data.status}`;
                    
                    if (data.status === 'success') {
                        // Update total score if provided
                        if (data.total_score !== undefined) {
                            document.querySelector('.student-info .score').textContent = 
                                `${data.total_score}/${data.passing_score}`;
                        }
                    }
                })
                .catch(error => {
                    feedbackDiv.textContent = 'Une erreur est survenue';
                    feedbackDiv.className = 'feedback error';
                });
            });
        });
    </script>
</body>
</html> 