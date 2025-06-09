<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check if registration ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$registration_id = $_GET['id'];

// Fetch registration details
try {
    $stmt = $conn->prepare("
        SELECT lr.*, us.full_name, us.department, us.year_of_study 
        FROM laptop_registrations lr 
        JOIN university_students us ON lr.student_id = us.id 
        WHERE lr.id = ?
    ");
    $stmt->execute([$registration_id]);
    $registration = $stmt->fetch();

    if (!$registration) {
        header("Location: dashboard.php?error=Registration not found");
        exit();
    }
} catch(PDOException $e) {
    header("Location: dashboard.php?error=Database error");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Registration - Laptop Sentinel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1 class="dashboard-title">Edit Laptop Registration</h1>
                </div>
                <div class="header-right">
                    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>

            <div class="edit-form-container">
                <div class="student-info">
                    <h3>Student Information</h3>
                    <p><strong>ID:</strong> <?php echo htmlspecialchars($registration['student_id']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($registration['full_name']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($registration['department']); ?></p>
                    <p><strong>Year of Study:</strong> <?php echo htmlspecialchars($registration['year_of_study']); ?></p>
                </div>

                <form action="process_registration.php" method="POST" class="edit-form">
                    <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registration['id']); ?>">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($registration['student_id']); ?>">
                    
                    <div class="form-group">
                        <label for="laptop_serial">Laptop Serial Number</label>
                        <input type="text" id="laptop_serial" name="laptop_serial" 
                               value="<?php echo htmlspecialchars($registration['laptop_serial']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="laptop_model">Laptop Model</label>
                        <input type="text" id="laptop_model" name="laptop_model" 
                               value="<?php echo htmlspecialchars($registration['laptop_model']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="not stolen" <?php echo $registration['status'] === 'not stolen' ? 'selected' : ''; ?>>Not Stolen</option>
                            <option value="stolen" <?php echo $registration['status'] === 'stolen' ? 'selected' : ''; ?>>Stolen</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Update Registration</button>
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 