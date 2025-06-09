<?php
require_once '../config/db_connect.php';

// Default admin credentials
$admin_id = 'adm/1234/15';
$admin_password = 'admin123'; // This is the default password

try {
    // Check if admin already exists
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $existing_admin = $stmt->fetch();

    if (!$existing_admin) {
        // Create new admin account
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (id, password) VALUES (?, ?)");
        $stmt->execute([$admin_id, $hashed_password]);
        
        echo "<h2>Admin account created successfully!</h2>";
        echo "<p>Admin ID: " . htmlspecialchars($admin_id) . "</p>";
        echo "<p>Password: " . htmlspecialchars($admin_password) . "</p>";
        echo "<p>Hashed Password: " . htmlspecialchars($hashed_password) . "</p>";
        echo "<a href='login.php'>Go to Login</a>";
    } else {
        echo "<h2>Admin account already exists!</h2>";
        echo "<p>Admin ID: " . htmlspecialchars($existing_admin['id']) . "</p>";
        echo "<p>Stored Hashed Password: " . htmlspecialchars($existing_admin['password']) . "</p>";
        echo "<p>Try logging in with:</p>";
        echo "<p>Admin ID: " . htmlspecialchars($admin_id) . "</p>";
        echo "<p>Password: " . htmlspecialchars($admin_password) . "</p>";
        echo "<a href='login.php'>Go to Login</a>";
    }
} catch(PDOException $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 