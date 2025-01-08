<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur de connexion: ' . $e->getMessage()
    ]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Debug information
        error_log("Login attempt - Email: " . $email);
        error_log("User found: " . ($user ? "Yes" : "No"));
        
        if ($user) {
            error_log("Password verification result: " . (password_verify($password, $user['password']) ? "Success" : "Failed"));
        }

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Set remember me cookie if checked
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
                
                // Store token in database
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
            }

            // Return success response
            echo json_encode([
                'status' => 'success',
                'message' => 'Connexion réussie! Redirection...',
                'redirect' => 'dashboard.php',
                'role' => $user['role']
            ]);
        } else {
            // Login failed
            echo json_encode([
                'status' => 'error',
                'message' => 'Email ou mot de passe incorrect'
            ]);
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' =>     'Erreur de connexion: ' . $e->getMessage()
        ]);
    }
}
?> 