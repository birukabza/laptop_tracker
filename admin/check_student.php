<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Student ID is required']);
    exit();
}

$student_id = trim($_GET['id']);

try {
    // Check if student exists
    $stmt = $conn->prepare("SELECT * FROM university_students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if ($student) {
        // Count existing laptop registrations with status 'not stolen'
        $stmt = $conn->prepare("SELECT COUNT(*) FROM laptop_registrations WHERE student_id = ? AND status = 'not stolen'");
        $stmt->execute([$student_id]);
        $laptop_count = $stmt->fetchColumn();

        echo json_encode([
            'exists' => true,
            'student' => [
                'id' => $student['id'],
                'full_name' => $student['full_name'],
                'department' => $student['department'],
                'year_of_study' => $student['year_of_study']
            ],
            'laptop_count' => $laptop_count
        ]);
    } else {
        echo json_encode([
            'exists' => false,
            'message' => 'Student not found in university database'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?> 