<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guard_id = trim($_POST['guard_id']);
    $password = trim($_POST['password']);

    if (empty($guard_id) || empty($password)) {
        header("Location: login.php?error=All fields are required.");
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT id, password FROM guards WHERE id = ?");
        $stmt->execute([$guard_id]);
        $guard = $stmt->fetch();

        if ($guard && password_verify($password, $guard['password'])) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['guard_id'] = $guard['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: login.php?error=Invalid Guard ID or Password.");
            exit();
        }
    } catch(PDOException $e) {
        header("Location: login.php?error=Database error: " . $e->getMessage());
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?> 