<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Student ID is required']);
    exit();
}

$student_id = trim($_GET['id']);

try {
    // Check if student exists in student_users table
    $stmt = $conn->prepare("SELECT su.*, us.department, us.year_of_study 
                           FROM student_users su 
                           JOIN university_students us ON su.student_id = us.id 
                           WHERE su.student_id = ?");
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
                'id' => $student['student_id'],
                'full_name' => $student['full_name'],
                'email' => $student['email'],
                'department' => $student['department'],
                'year_of_study' => $student['year_of_study'],
                'is_registered' => true
            ],
            'laptop_count' => $laptop_count
        ]);
    } else {
        // Check if student exists in university_students but not registered
        $stmt = $conn->prepare("SELECT * FROM university_students WHERE id = ?");
        $stmt->execute([$student_id]);
        $univ_student = $stmt->fetch();

        if ($univ_student) {
            echo json_encode([
                'exists' => true,
                'student' => [
                    'id' => $univ_student['id'],
                    'full_name' => $univ_student['full_name'],
                    'department' => $univ_student['department'],
                    'year_of_study' => $univ_student['year_of_study'],
                    'is_registered' => false
                ],
                'message' => 'Student found in university database but not registered in the system. Please ask the student to sign up first.'
            ]);
        } else {
            echo json_encode([
                'exists' => false,
                'message' => 'Student not found in university database'
            ]);
        }
    }
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?> 