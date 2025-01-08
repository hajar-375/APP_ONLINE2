<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['role'] === 'student') {
    // Fetch exams for today
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT * 
        FROM exams 
        WHERE is_active = 1 
          AND exam_date = ?
    ");
    $stmt->execute([$today]);
    $examsToday = $stmt->fetchAll();
} elseif ($user['role'] === 'teacher') {
    // Fetch teacher's exams
    $stmt = $pdo->prepare("
        SELECT e.*, 
               COUNT(DISTINCT se.id) as total_attempts,
               COUNT(DISTINCT CASE WHEN se.status = 'completed' THEN se.id END) as passed_attempts
        FROM exams e
        LEFT JOIN student_exams se ON e.id = se.exam_id
        WHERE e.teacher_id = ?
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $exams = $stmt->fetchAll();
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Exam Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            background: var(--background-color);
        }

        .results-sidebar {
            width: 400px;
            background: var(--card-background);
            padding: 2rem;
            box-shadow: -2px 0 10px var(--shadow-color);
            overflow-y: auto;
            max-height: calc(100vh - 70px);
        }

        .student-list {
            margin-top: 1rem;
        }

        .student-card {
            background: rgba(33, 150, 243, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .student-card:hover {
            transform: translateX(-5px);
            box-shadow: 5px 5px 15px var(--shadow-color);
        }

        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .student-name {
            font-weight: bold;
            color: var(--primary-color);
        }

        .student-score {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .student-details {
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            color: white;
        }

        .status-completed { background: var(--success-color); }
        .status-failed { background: var(--danger-color); }
        .status-in_progress { background: var(--warning-color); }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-grade {
            background: var(--primary-color);
            color: white;
        }

        .btn-view {
            background: var(--secondary-color);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px var(--shadow-color);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .filter-section {
            margin-bottom: 1rem;
        }

        .filter-section select {
            width: 100%;
            padding: 0.5rem;
            border: 2px solid var(--input-border);
            border-radius: 5px;
            background: var(--card-background);
            color: var(--text-color);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--card-background);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn-create-exam {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-create-exam:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px var(--shadow-color);
        }

        .btn-create-exam i {
            font-size: 1.1rem;
        }

        .exam-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .exam-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .status-available {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-in-progress {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-completed {
            background: #e8f5e9;
            color: #388e3c;
        }

        .exam-details {
            display: flex;
            gap: 1.5rem;
            margin: 1rem 0;
            color: #666;
        }

        .exam-details span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-start-exam, .btn-view-results {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-start-exam {
            background: #1976d2;
            color: white;
        }

        .btn-start-exam:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }

        .btn-view-results {
            background: #4caf50;
            color: white;
        }

        .btn-view-results:hover {
            background: #43a047;
            transform: translateY(-2px);
        }

        .no-exams {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
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
            <span>Bienvenue, <?php echo htmlspecialchars($user['name']); ?></span>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </nav>

    <div class="dashboard-container">
        <?php if ($user['role'] === 'teacher'): ?>
            <!-- Teacher Dashboard -->
            <div class="main-content">
                <div class="dashboard-header">
                    <h2>Mes Examens</h2>
                    <a href="create_exam.php" class="btn-create-exam">
                        <i class="fas fa-plus"></i> Créer un Examen
                    </a>
                </div>
                <div class="exam-grid">
                    <?php foreach ($exams as $exam): ?>
                        <div class="exam-card">
                            <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                            <p><?php echo htmlspecialchars($exam['description']); ?></p>
                            <div class="exam-stats">
                                <div class="stat">
                                    <span class="stat-value"><?php echo $exam['total_attempts']; ?></span>
                                    <span class="stat-label">Participants</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value"><?php echo $exam['passed_attempts']; ?></span>
                                    <span class="stat-label">Réussis</span>
                                </div>
                            </div>
                            <button class="btn-view-results" onclick="loadExamResults(<?php echo $exam['id']; ?>)">
                                Voir les résultats
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Results Sidebar -->
            <div class="results-sidebar">
                <div class="results-header">
                    <h3>Résultats des Étudiants</h3>
                </div>

                <div class="filter-section">
                    <select id="examFilter" onchange="filterResults()">
                        <option value="">Tous les examens</option>
                        <?php foreach ($exams as $exam): ?>
                            <option value="<?php echo $exam['id']; ?>">
                                <?php echo htmlspecialchars($exam['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-value" id="totalStudents">-</div>
                        <div class="stat-label">Total Étudiants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="passRate">-</div>
                        <div class="stat-label">Taux de Réussite</div>
                    </div>
                </div>

                <div class="student-list" id="studentResults">
                    <!-- Student results will be loaded here -->
                </div>
            </div>

            <?php elseif ($user['role'] === 'student'): ?>
                <div class="main-content">
                    <h2>Examens d'aujourd'hui</h2>
                    <?php if (!empty($examsToday)): ?>
                        <div class="exam-grid">
                            <?php foreach ($examsToday as $exam): ?>
                                <div class="exam-card">
                                    <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($exam['description']); ?></p>
                                    <div class="exam-details">
                                        <span>Durée: <?php echo $exam['duration']; ?> minutes</span>
                                        <span>Date: <?php echo date('d/m/Y', strtotime($exam['exam_date'])); ?></span>
                                    </div>
                                    <button class="btn-start-exam" onclick="startExam(<?php echo $exam['id']; ?>)">
                                        Commencer l'Examen
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-exams">Aucun examen prévu pour aujourd'hui.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

    </div>

    <script>
        // Load exam results for teachers
        function loadExamResults(examId) {
            fetch(`get_exam_results.php?exam_id=${examId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('totalStudents').textContent = data.stats.total_students;
                        document.getElementById('passRate').textContent = data.stats.pass_rate + '%';
                        
                        const studentList = document.getElementById('studentResults');
                        studentList.innerHTML = data.results.map(student => `
                            <div class="student-card">
                                <div class="student-header">
                                    <span class="student-name">${student.name}</span>
                                    <span class="student-score">${student.score}/${student.total_points}</span>
                                </div>
                                <div class="student-details">
                                    <div>${student.email}</div>
                                    <div>
                                        <span class="status-badge status-${student.status}">
                                            ${student.status === 'completed' ? 'Réussi' : 
                                              student.status === 'failed' ? 'Échoué' : 'En cours'}
                                        </span>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn-action btn-grade" 
                                            onclick="location.href='view_student_exam.php?id=${student.attempt_id}'">
                                        Noter
                                    </button>
                                    <button class="btn-action btn-view"
                                            onclick="location.href='view_student_exam.php?id=${student.attempt_id}'">
                                        Détails
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }
                });
        }

        // Load available exams for students
        function loadAvailableExams() {
            fetch('load_exams.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('available-exams').innerHTML = data.html;
                    } else {
                        alert('Erreur lors du chargement des examens: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue lors du chargement des examens');
                });
        }

        // Filter results by exam
        function filterResults() {
            const examId = document.getElementById('examFilter').value;
            if (examId) {
                loadExamResults(examId);
            }
        }

        // Load initial data
        <?php if ($user['role'] === 'student'): ?>
        loadAvailableExams();
        <?php else: ?>
        // Load results for the first exam if exists
        <?php if (!empty($exams)): ?>
        loadExamResults(<?php echo $exams[0]['id']; ?>);
        <?php endif; ?>
        <?php endif; ?>

        function startExam(examId) {
            if (confirm('Êtes-vous prêt à commencer l\'examen ? Le chronomètre démarrera immédiatement.')) {
                window.location.href = `take_exam.php?exam_id=${examId}`;
            }
        }

        function viewResults(examId) {
            window.location.href = `view_results.php?exam_id=${examId}`;
        }

        // Load exams when page loads
        document.addEventListener('DOMContentLoaded', loadAvailableExams);
    </script>
</body>
</html> 