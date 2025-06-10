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
    if (isset($_POST['student_id'])) {
        $student_id = trim($_POST['student_id']);

        if (empty($student_id)) {
            echo json_encode(['success' => false, 'message' => 'Student ID is required.']);
            exit();
        }

        try {
            // Get student details (from university_students and student_users)
            $stmt = $conn->prepare("SELECT us.id, us.full_name, us.department, us.year_of_study, us.phone, su.email, su.created_at as user_created_at FROM university_students us LEFT JOIN student_users su ON us.id = su.student_id WHERE us.id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student) {
                // Get all registered laptops for this student
                $laptops_stmt = $conn->prepare("SELECT id, laptop_serial, laptop_model, registration_date, status FROM laptop_registrations WHERE student_id = ?");
                $laptops_stmt->execute([$student_id]);
                $laptops = $laptops_stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'student' => $student,
                    'laptops' => $laptops
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student not found.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }

    } elseif (isset($_POST['laptop_serial'])) {
        $laptop_serial = trim($_POST['laptop_serial']);

        if (empty($laptop_serial)) {
            echo json_encode(['success' => false, 'message' => 'Laptop serial number is required.']);
            exit();
        }

        try {
            // Get laptop details
            $laptop_stmt = $conn->prepare("SELECT lr.id, lr.student_id, lr.laptop_serial, lr.laptop_model, lr.registration_date, lr.status, rl.report_id, rl.status as report_status, rl.report_date, rl.found_date FROM laptop_registrations lr LEFT JOIN reported_laptops rl ON lr.id = rl.laptop_id WHERE lr.laptop_serial = ?");
            $laptop_stmt->execute([$laptop_serial]);
            $laptop = $laptop_stmt->fetch(PDO::FETCH_ASSOC);

            if ($laptop) {
                // Get owner details
                $owner_stmt = $conn->prepare("SELECT us.id, us.full_name, us.department, us.year_of_study, us.phone, su.email FROM university_students us LEFT JOIN student_users su ON us.id = su.student_id WHERE us.id = ?");
                $owner_stmt->execute([$laptop['student_id']]);
                $owner = $owner_stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'laptop' => $laptop,
                    'owner' => $owner
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Laptop not found or not registered.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?> 