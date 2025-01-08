<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
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

    // Get all exams created by this teacher
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE teacher_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $exams = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats des Examens - Tableau de Bord Enseignant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .results-container {
            padding: 2rem;
        }

        .exam-results {
            background: var(--card-background);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .exam-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: rgba(33, 150, 243, 0.1);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .students-table th,
        .students-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--shadow-color);
        }

        .students-table th {
            background: rgba(33, 150, 243, 0.1);
            font-weight: 600;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .status-completed {
            background: var(--success-color);
            color: white;
        }

        .status-failed {
            background: var(--danger-color);
            color: white;
        }

        .status-in-progress {
            background: var(--warning-color);
            color: white;
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

        .search-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-filter input,
        .search-filter select {
            padding: 0.5rem 1rem;
            border: 2px solid var(--input-border);
            border-radius: 5px;
            flex: 1;
        }

        .export-btn {
            padding: 0.5rem 1rem;
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-container {
            margin-top: 2rem;
            height: 300px;
            position: relative;
        }
    </style>
</head>
<body>
    <div class="results-container">
        <?php foreach ($exams as $exam): ?>
            <?php
            // Get student results for this exam
            $stmt = $pdo->prepare("
                SELECT 
                    se.*,
                    u.name as student_name,
                    u.email as student_email,
                    COUNT(DISTINCT sa.id) as answered_questions,
                    COUNT(DISTINCT q.id) as total_questions
                FROM student_exams se
                JOIN users u ON se.student_id = u.id
                LEFT JOIN student_answers sa ON se.id = sa.student_exam_id
                LEFT JOIN questions q ON q.exam_id = se.exam_id
                WHERE se.exam_id = ?
                GROUP BY se.id
                ORDER BY se.created_at DESC
            ");
            $stmt->execute([$exam['id']]);
            $results = $stmt->fetchAll();

            // Calculate statistics
            $total_students = count($results);
            $passed_students = 0;
            $total_score = 0;
            $highest_score = 0;

            foreach ($results as $result) {
                if ($result['status'] === 'completed') {
                    $passed_students++;
                }
                if ($result['score'] > $highest_score) {
                    $highest_score = $result['score'];
                }
                $total_score += $result['score'];
            }

            $average_score = $total_students > 0 ? round($total_score / $total_students, 2) : 0;
            ?>

            <div class="exam-results">
                <div class="exam-header">
                    <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
                    <button class="export-btn" onclick="exportResults(<?php echo $exam['id']; ?>)">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                </div>

                <div class="exam-stats">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_students; ?></div>
                        <div class="stat-label">Étudiants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $passed_students; ?></div>
                        <div class="stat-label">Réussis</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $average_score; ?></div>
                        <div class="stat-label">Score Moyen</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $highest_score; ?></div>
                        <div class="stat-label">Meilleur Score</div>
                    </div>
                </div>

                <div class="search-filter">
                    <input type="text" placeholder="Rechercher un étudiant..." onkeyup="filterStudents(this, <?php echo $exam['id']; ?>)">
                    <select onchange="filterStatus(this, <?php echo $exam['id']; ?>)">
                        <option value="">Tous les statuts</option>
                        <option value="completed">Réussi</option>
                        <option value="failed">Échoué</option>
                        <option value="in_progress">En cours</option>
                    </select>
                </div>

                <table class="students-table" id="exam-<?php echo $exam['id']; ?>-table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Email</th>
                            <th>Score</th>
                            <th>Questions Répondues</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['student_email']); ?></td>
                                <td><?php echo $result['score'] ?? 'N/A'; ?>/<?php echo $exam['passing_score']; ?></td>
                                <td><?php echo $result['answered_questions']; ?>/<?php echo $result['total_questions']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $result['status']; ?>">
                                        <?php 
                                        switch($result['status']) {
                                            case 'completed':
                                                echo 'Réussi';
                                                break;
                                            case 'failed':
                                                echo 'Échoué';
                                                break;
                                            case 'in_progress':
                                                echo 'En cours';
                                                break;
                                            default:
                                                echo $result['status'];
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($result['created_at'])); ?></td>
                                <td>
                                    <button class="btn-grade" onclick="viewDetails(<?php echo $result['id']; ?>)">
                                        Détails
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="chart-container" id="chart-<?php echo $exam['id']; ?>">
                    <!-- Chart will be rendered here -->
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Filter functions
        function filterStudents(input, examId) {
            const filter = input.value.toLowerCase();
            const table = document.getElementById(`exam-${examId}-table`);
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const studentName = rows[i].cells[0].textContent.toLowerCase();
                const studentEmail = rows[i].cells[1].textContent.toLowerCase();
                rows[i].style.display = 
                    studentName.includes(filter) || studentEmail.includes(filter) 
                        ? '' 
                        : 'none';
            }
        }

        function filterStatus(select, examId) {
            const filter = select.value.toLowerCase();
            const table = document.getElementById(`exam-${examId}-table`);
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const status = rows[i].cells[4].textContent.toLowerCase();
                rows[i].style.display = 
                    filter === '' || status.includes(filter)
                        ? ''
                        : 'none';
            }
        }

        function viewDetails(studentExamId) {
            // Redirect to detailed view
            window.location.href = `view_student_exam.php?id=${studentExamId}`;
        }

        function exportResults(examId) {
            // Export to CSV
            window.location.href = `export_results.php?exam_id=${examId}`;
        }

        // Initialize charts for each exam
        <?php foreach ($exams as $exam): ?>
        new Chart(document.getElementById('chart-<?php echo $exam['id']; ?>'), {
            type: 'bar',
            data: {
                labels: ['0-20', '20-40', '40-60', '60-80', '80-100'],
                datasets: [{
                    label: 'Distribution des scores',
                    data: [
                        <?php
                        $score_ranges = array_fill(0, 5, 0);
                        $stmt = $pdo->prepare("SELECT score FROM student_exams WHERE exam_id = ? AND score IS NOT NULL");
                        $stmt->execute([$exam['id']]);
                        while ($score = $stmt->fetch(PDO::FETCH_COLUMN)) {
                            $range = floor($score / 20);
                            if ($range >= 0 && $range < 5) {
                                $score_ranges[$range]++;
                            }
                        }
                        echo implode(',', $score_ranges);
                        ?>
                    ],
                    backgroundColor: 'rgba(33, 150, 243, 0.5)',
                    borderColor: 'rgba(33, 150, 243, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endforeach; ?>
    </script>
</body>
</html> 