<?php
session_start();
require_once '../config/db_connect.php';

// If guard is already logged in, redirect to dashboard
if(isset($_SESSION['guard_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Login - Laptop Sentinel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h1 class="app-title">Laptop Sentinel</h1>
            <p class="login-subtitle">Guard Login</p>

            <?php if(isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="process_login.php" method="POST">
                <div class="form-group">
                    <label for="guard_id">Guard ID</label>
                    <input type="text" id="guard_id" name="guard_id" placeholder="grd/1234/14" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </div>
</body>
</html> 