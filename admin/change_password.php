<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
	header("Location: login.php");
	exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$currentPassword = $_POST['current_password'] ?? '';
	$newPassword = $_POST['new_password'] ?? '';
	$confirmPassword = $_POST['confirm_password'] ?? '';

	if ($newPassword !== $confirmPassword) {
		$error = 'New passwords do not match';
	} elseif (strlen($newPassword) < 8) {
		$error = 'New password must be at least 8 characters';
	} else {
		try {
			$stmt = $conn->prepare('SELECT password FROM admins WHERE id = ?');
			$stmt->execute([$_SESSION['admin_id']]);
			$admin = $stmt->fetch();
			if (!$admin || !password_verify($currentPassword, $admin['password'])) {
				$error = 'Current password is incorrect';
			} else {
				$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
				$update = $conn->prepare('UPDATE admins SET password = ? WHERE id = ?');
				$update->execute([$newHash, $_SESSION['admin_id']]);
				$success = 'Password updated successfully';
			}
		} catch (PDOException $e) {
			$error = 'Database error: ' . $e->getMessage();
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Change Password - Admin</title>
	<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
	<div class="login-container">
		<h1>Change Password</h1>
		<?php if ($error): ?>
			<div class="error-message"><?php echo htmlspecialchars($error); ?></div>
		<?php endif; ?>
		<?php if ($success): ?>
			<div class="success-message"><?php echo htmlspecialchars($success); ?></div>
		<?php endif; ?>
		<form method="POST" action="">
			<div class="form-group">
				<label for="current_password">Current Password</label>
				<input type="password" id="current_password" name="current_password" required>
			</div>
			<div class="form-group">
				<label for="new_password">New Password</label>
				<input type="password" id="new_password" name="new_password" required>
			</div>
			<div class="form-group">
				<label for="confirm_password">Confirm New Password</label>
				<input type="password" id="confirm_password" name="confirm_password" required>
			</div>
			<button type="submit" class="btn btn-primary">Update Password</button>
		</form>
		<p style="margin-top:1rem;"><a href="dashboard.php">Back to Dashboard</a></p>
	</div>
</div>
</body>
</html>
