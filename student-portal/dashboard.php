<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Handle laptop reporting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_laptop'])) {
    $laptop_id = $_POST['laptop_id'];
    
    // Check if laptop is already reported
    $stmt = $conn->prepare("SELECT * FROM reported_laptops WHERE laptop_id = ? AND status = 'reported'");
    $stmt->execute([$laptop_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        // Report the laptop
        $stmt = $conn->prepare("INSERT INTO reported_laptops (laptop_id, student_id) VALUES (?, ?)");
        if ($stmt->execute([$laptop_id, $student_id])) {
            // Update laptop status in laptop_registrations table
            $stmt = $conn->prepare("UPDATE laptop_registrations SET status = 'stolen' WHERE id = ?");
            $stmt->execute([$laptop_id]);
            $_SESSION['success_message'] = "Laptop reported successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to report laptop. Please try again.";
        }
    } else {
        $_SESSION['error_message'] = "This laptop has already been reported.";
    }
    header("Location: dashboard.php");
    exit();
}

// Handle laptop found claim
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claim_found'])) {
    $laptop_id = $_POST['laptop_id'];
    
    // Update reported_laptops table
    $stmt = $conn->prepare("UPDATE reported_laptops SET status = 'found', found_date = CURRENT_TIMESTAMP WHERE laptop_id = ? AND student_id = ? AND status = 'reported'");
    if ($stmt->execute([$laptop_id, $student_id])) {
        // Update laptop status in laptop_registrations table
        $stmt = $conn->prepare("UPDATE laptop_registrations SET status = 'not stolen' WHERE id = ?");
        $stmt->execute([$laptop_id]);
        $_SESSION['success_message'] = "Laptop marked as found successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update laptop status. Please try again.";
    }
    header("Location: dashboard.php");
    exit();
}

// Get student's registered laptops
$stmt = $conn->prepare("
    SELECT l.*, 
           CASE 
               WHEN r.status = 'reported' THEN 'Reported'
               WHEN r.status = 'found' THEN 'Found'
               ELSE l.status 
           END as current_status
    FROM laptop_registrations l
    LEFT JOIN reported_laptops r ON l.id = r.laptop_id AND r.status = 'reported'
    WHERE l.student_id = ?
    ORDER BY l.id DESC
");
$stmt->execute([$student_id]);
$laptops = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="header-left">
                    <h2 class="dashboard-title">My Registered Laptops</h2>
                </div>
                <div class="header-right">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
                    <a class="btn btn-secondary" href="logout.php">Logout</a>
                </div>
            </div>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Serial Number</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laptops as $laptop): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($laptop['laptop_serial']); ?></td>
                                <td><?php echo htmlspecialchars($laptop['laptop_model']); ?></td>
                                <td><?php echo htmlspecialchars($laptop['current_status']); ?></td>
                                <td>
                                    <?php if ($laptop['current_status'] == 'not stolen'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="laptop_id" value="<?php echo $laptop['id']; ?>">
                                            <button type="submit" name="report_laptop" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to report this laptop as stolen?')">Report Stolen</button>
                                        </form>
                                    <?php elseif ($laptop['current_status'] == 'Reported'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="laptop_id" value="<?php echo $laptop['id']; ?>">
                                            <button type="submit" name="claim_found" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to mark this laptop as found?')">Mark as Found</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 