<?php
session_start();
require_once '../config/db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];

    // Verify if student exists in the database
    $stmt = $conn->prepare("SELECT * FROM university_students WHERE id = ?");
    $stmt->execute([$student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Check if student is already registered
        $stmt = $conn->prepare("SELECT * FROM student_users WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $user_result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_result) {
            $error = "You are already registered. Please login instead.";
        } else {
            // Register the student
            $stmt = $conn->prepare("INSERT INTO student_users (student_id, password, full_name) VALUES (?, ?, ?)");
            if ($stmt->execute([$student_id, $password, $full_name])) {
                $success = "Registration successful! Please login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    } else {
        $error = "Student ID not found in the database. Please verify your student ID.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Signup</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1>Student Signup</h1>
            </div>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" required>
                </div>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Sign Up</button>
            </form>
            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html> 