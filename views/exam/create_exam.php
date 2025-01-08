<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: login.php');
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
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Examen - Exam Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .create-exam-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--card-background);
            border-radius: 10px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }


        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--input-border);
            border-radius: 5px;
            background: var(--background-color);
            color: var(--text-color);
            font-size: 1rem;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .questions-container {
            margin-top: 2rem;
        }

        .question-card {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .question-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .answer-container {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .answer-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.8rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .answer-row:hover {
            background: #e9ecef;
        }

        .answer-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .answer-input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .remove-answer {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .remove-answer:hover {
            background: #bb2d3b;
        }

        .add-answer-btn {
            width: 100%;
            padding: 0.5rem;
            margin-top: 1rem;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .add-answer-btn:hover {
            background: #2c3e50;
        }

        .btn-add-question {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
            transition: all 0.3s ease;
        }

        .btn-add-question:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px var(--shadow-color);
        }

        .btn-submit {
            background: #2c3e50;
            color:   wheat;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            width: 100%;
            margin-top: 2rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px var(--shadow-color);
        }

        .answer-inputs {
            margin-top: 1rem;
        }

        .btn-add-answer {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-add-answer:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px var(--shadow-color);
        }

        .question-type-select {
            margin-bottom: 1rem;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 200px;
        }

        .answer-type-radio {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.8rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .answer-type-text {
            margin-bottom: 0.8rem;
            padding: 0.5rem;
        }

        .answer-type-text textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .answer-type-checkbox {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.8rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .answer-type-checkbox:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <img src="img.png" alt="Logo" class="nav-logo">
            <span>Exam Online</span>
        </div>
        <div class="nav-user">
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour au Dashboard
            </a>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </nav>

    <div class="create-exam-container">
        <h2><i class="fas fa-edit"></i> Créer un Nouvel Examen</h2>
        
        <form id="examForm" onsubmit="submitExam(event)">
            <div class="form-group">
                <label for="title">Titre de l'Examen</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group">
                <label for="duration">Durée (minutes)</label>
                <input type="number" id="duration" name="duration" min="1" required>
            </div>

            <div class="form-group">
    <label for="exam_date">Date de l'Examen</label>
    <input type="date" id="exam_date" name="exam_date" required>
</div>


            <div class="form-group">
                <label for="passing_score">Score Minimum pour Réussir (%)</label>
                <input type="number" id="passing_score" name="passing_score" min="0" max="100" required>
            </div>

            <div class="questions-container" id="questionsContainer">
                <!-- Questions will be added here dynamically -->
            </div>

            <button type="button" class="btn-add-question" onclick="addQuestion()">
                <i class="fas fa-plus"></i> Ajouter une Question
            </button>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Créer l'Examen
            </button>
        </form>
    </div>

    <script>
        let questionCount = 0;

        function addQuestion() {
            questionCount++;
            const questionHtml = `
                <div class="question-card" id="question${questionCount}">
                    <div class="question-header">
                        <span class="question-number">Question ${questionCount}</span>
                        <button type="button" class="remove-question" onclick="removeQuestion(${questionCount})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>Nom de la Question</label>
                        <input type="text" name="questions[${questionCount}][name]" 
                               placeholder="Ex: Question sur les bases de données" required>
                    </div>

                    <div class="form-group">
                        <label>Texte de la Question</label>
                        <textarea name="questions[${questionCount}][text]" 
                                 placeholder="Ex: Qu'est-ce qu'une clé primaire en SQL ?" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Points</label>
                        <input type="number" name="questions[${questionCount}][points]" 
                               min="1" value="1" required>
                    </div>

                    <div class="form-group">
                        <label>Type de Réponse</label>
                        <select class="question-type-select" onchange="changeAnswerType(${questionCount}, this.value)">
                            <option value="radio">Choix unique (Radio)</option>
                            <option value="checkbox">Choix multiple (Checkbox)</option>
                            <option value="text">Réponse texte libre</option>
                        </select>
                    </div>

                    <div class="answer-container" id="answers${questionCount}">
                        <h4>Réponses</h4>
                        <div class="answer-list">
                            <div class="answer-type-radio">
                                <input type="radio" class="answer-radio" 
                                       name="questions[${questionCount}][correct_answer]" value="1">
                                <input type="text" class="answer-input" 
                                       name="questions[${questionCount}][answers][1][text]" 
                                       placeholder="Réponse 1" required>
                                <button type="button" class="remove-answer" onclick="removeAnswer(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="answer-type-radio">
                                <input type="radio" class="answer-radio" 
                                       name="questions[${questionCount}][correct_answer]" value="2">
                                <input type="text" class="answer-input" 
                                       name="questions[${questionCount}][answers][2][text]" 
                                       placeholder="Réponse 2" required>
                                <button type="button" class="remove-answer" onclick="removeAnswer(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="add-answer-btn" onclick="addAnswer(${questionCount})">
                            <i class="fas fa-plus"></i> Ajouter une Réponse
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('questionsContainer').insertAdjacentHTML('beforeend', questionHtml);
        }

        function changeAnswerType(questionId, type) {
            const answerContainer = document.getElementById(`answers${questionId}`);
            if (type === 'text') {
                answerContainer.innerHTML = `
                    <h4>Réponse Correcte</h4>
                    <div class="answer-type-text">
                        <textarea name="questions[${questionId}][correct_answer]" 
                                placeholder="Entrez la réponse correcte ici" required></textarea>
                    </div>
                `;
            } else if (type === 'checkbox') {
                answerContainer.innerHTML = `
                    <h4>Réponses (Sélectionnez toutes les réponses correctes)</h4>
                    <div class="answer-list">
                        <div class="answer-type-checkbox">
                            <input type="checkbox" class="answer-checkbox" 
                                   name="questions[${questionId}][correct_answers][]" value="1">
                            <input type="text" class="answer-input" 
                                   name="questions[${questionId}][answers][1][text]" 
                                   placeholder="Réponse 1" required>
                            <button type="button" class="remove-answer" onclick="removeAnswer(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="answer-type-checkbox">
                            <input type="checkbox" class="answer-checkbox" 
                                   name="questions[${questionId}][correct_answers][]" value="2">
                            <input type="text" class="answer-input" 
                                   name="questions[${questionId}][answers][2][text]" 
                                   placeholder="Réponse 2" required>
                            <button type="button" class="remove-answer" onclick="removeAnswer(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="add-answer-btn" onclick="addCheckboxAnswer(${questionId})">
                        <i class="fas fa-plus"></i> Ajouter une Réponse
                    </button>
                `;
            } else {
                answerContainer.innerHTML = `
                    <h4>Réponses (Sélectionnez la réponse correcte)</h4>
                    <div class="answer-list">
                        <div class="answer-type-radio">
                            <input type="radio" class="answer-radio" 
                                   name="questions[${questionId}][correct_answer]" value="1" required>
                            <input type="text" class="answer-input" 
                                   name="questions[${questionId}][answers][1][text]" 
                                   placeholder="Réponse 1" required>
                            <button type="button" class="remove-answer" onclick="removeAnswer(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="answer-type-radio">
                            <input type="radio" class="answer-radio" 
                                   name="questions[${questionId}][correct_answer]" value="2" required>
                            <input type="text" class="answer-input" 
                                   name="questions[${questionId}][answers][2][text]" 
                                   placeholder="Réponse 2" required>
                            <button type="button" class="remove-answer" onclick="removeAnswer(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="add-answer-btn" onclick="addAnswer(${questionId})">
                        <i class="fas fa-plus"></i> Ajouter une Réponse
                    </button>
                `;
            }
        }

        function addAnswer(questionId) {
            const answerList = document.querySelector(`#answers${questionId} .answer-list`);
            const answerCount = answerList.children.length + 1;
            
            const answerHtml = `
                <div class="answer-type-radio">
                    <input type="radio" class="answer-radio" 
                           name="questions[${questionId}][correct_answer]" value="${answerCount}">
                    <input type="text" class="answer-input" 
                           name="questions[${questionId}][answers][${answerCount}][text]" 
                           placeholder="Réponse ${answerCount}" required>
                    <button type="button" class="remove-answer" onclick="removeAnswer(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            answerList.insertAdjacentHTML('beforeend', answerHtml);
        }

        function addCheckboxAnswer(questionId) {
            const answerList = document.querySelector(`#answers${questionId} .answer-list`);
            const answerCount = answerList.children.length + 1;
            
            const answerHtml = `
                <div class="answer-type-checkbox">
                    <input type="checkbox" class="answer-checkbox" 
                           name="questions[${questionId}][correct_answers][]" value="${answerCount}">
                    <input type="text" class="answer-input" 
                           name="questions[${questionId}][answers][${answerCount}][text]" 
                           placeholder="Réponse ${answerCount}" required>
                    <button type="button" class="remove-answer" onclick="removeAnswer(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            answerList.insertAdjacentHTML('beforeend', answerHtml);
        }

        function removeQuestion(questionId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette question ?')) {
                document.getElementById(`question${questionId}`).remove();
            }
        }

        function removeAnswer(button) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette réponse ?')) {
                const answerElement = button.closest('.answer-type-radio') || button.closest('.answer-type-checkbox');
                if (answerElement) {
                    answerElement.remove();
                }
            }
        }

        function submitExam(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('process_exam.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Examen créé avec succès!');
                    window.location.href = 'dashboard.php';
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la création de l\'examen');
            });
        }

        // Add first question automatically
        addQuestion();
    </script>
</body>
</html>
?> 