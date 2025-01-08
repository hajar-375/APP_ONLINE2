<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Your MySQL username
define('DB_PASS', '');         // Your MySQL password
define('DB_NAME', 'exam_online');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>