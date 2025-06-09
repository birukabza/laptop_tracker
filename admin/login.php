<?php
session_start();
if(isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Laptop Sentinel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1>Laptop Sentinel</h1>
                <p>Admin Login</p>
            </div>
            <form action="process_login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="admin_id">Admin ID</label>
                    <input type="text" id="admin_id" name="admin_id" placeholder="adm/1234/15" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Login</button>
            </form>
            <?php if(isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 