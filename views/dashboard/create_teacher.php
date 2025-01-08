<?php
// Database connection
$host = 'localhost';
$dbname = 'exam_online';
$username = 'root';
$password = '';
$port = '3306';

try {
 $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $username,$password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Simple teacher information
    $teacher = [
        'name' => 'Teacher',
        'email' => 'test@test.com',
        'password' => '123456',
        'role' => 'teacher'
    ];

    echo "<html><head><style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
        .credentials { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .verify { background: #e8f5e9; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style></head><body>";

    // First, verify database connection
    echo "<div class='verify'>Database connection successful!</div>";

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$teacher['email']]);
    if ($stmt->fetch()) {
        // If exists, delete the old account
        $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
        $stmt->execute([$teacher['email']]);
        echo "<div class='verify'>Removed existing account for clean setup.</div>";
    }

    // Hash password and insert teacher
    $hashed_password = password_hash($teacher['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$teacher['name'], $teacher['email'], $hashed_password, $teacher['role']]);

    // Verify the account was created
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$teacher['email']]);
    $created_user = $stmt->fetch();

    if ($created_user) {
        echo "<div class='success'>Teacher account created and verified successfully!</div>";
        echo "<div class='credentials'>";
        echo "<strong>Name:</strong> {$teacher['name']}<br>";
        echo "<strong>Email:</strong> {$teacher['email']}<br>";
        echo "<strong>Password:</strong> {$teacher['password']}<br>";
        echo "</div>";

        // Verify password hash
        if (password_verify($teacher['password'], $created_user['password'])) {
            echo "<div class='verify'>Password hash verification successful!</div>";
        }
    }

    echo "<h3>Login Credentials (Copy these exactly):</h3>";
    echo "<div class='credentials' style='font-size: 1.2em;'>";
    echo "Email: test@test.com<br>";
    echo "Password: 123456";
    echo "</div>";

    echo "<h3>You can now login at login.php with these credentials</h3>";
    echo "</body></html>";

} catch(PDOException $e) {
    echo "<div class='error'>Database Error: " . $e->getMessage() . "</div>";
}
?> 