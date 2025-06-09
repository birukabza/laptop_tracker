<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

// Check if guard is logged in
if (!isset($_SESSION['guard_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ceased_id = (int)($_POST['id'] ?? 0);

    if ($action === 'release' && $ceased_id > 0) {
        try {
            $stmt = $conn->prepare("UPDATE ceased_laptops SET status = 'returned' WHERE id = ?");
            if ($stmt->execute([$ceased_id])) {
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Laptop successfully marked as returned.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No ceased laptop found with that ID or already returned.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update laptop status.']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action or missing ID.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
?> 