<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = trim($_POST['admin_id']);
    $password = $_POST['password'];

    // Validate admin ID format
    if (!preg_match('/^adm\/\d{4}\/\d{2}$/', $admin_id)) {
        header("Location: login.php?error=Invalid admin ID format");
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();

        if ($admin) {
            // Debug information
            error_log("Attempting login for admin: " . $admin_id);
            error_log("Stored hash: " . $admin['password']);
            error_log("Password verification result: " . (password_verify($password, $admin['password']) ? 'true' : 'false'));

            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                header("Location: dashboard.php");
                exit();
            } else {
                header("Location: login.php?error=Invalid password or admin ID");
                exit();
            }
        } else {
            header("Location: login.php?error=Admin ID not found");
            exit();
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        header("Location: login.php?error=Database error");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?> 