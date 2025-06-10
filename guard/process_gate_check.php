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
    $action = $_POST['action'] ?? 'verify'; // Default action is 'verify'

    if ($action === 'verify') {
        $student_id = trim($_POST['student_id']);
        $laptop_serial = trim($_POST['laptop_serial']);

        if (empty($student_id) || empty($laptop_serial)) {
            echo json_encode(['success' => false, 'message' => 'Student ID and Laptop Serial are required.', 'reason_ceased' => '']);
            exit();
        }

        try {
            // 1. Check if student exists in university_students table
            $stmt = $conn->prepare("SELECT full_name, department, year_of_study FROM university_students WHERE id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();

            if (!$student) {
                echo json_encode([
                    'success' => false,
                    'message' => "Student with ID '$student_id' not found in university database.",
                    'reason_ceased' => 'Student not found'
                ]);
                exit();
            }

            // Check if student is signed up (exists in student_users)
            $stmt = $conn->prepare("SELECT * FROM student_users WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $signed_up = $stmt->fetch();

            if (!$signed_up) {
                echo json_encode([
                    'success' => false,
                    'message' => "Student with ID '$student_id' is not signed up. Please ask the student to sign up before verification.",
                    'reason_ceased' => 'Student not signed up'
                ]);
                exit();
            }

            // 2. Check laptop registration for the student
            $stmt = $conn->prepare("SELECT * FROM laptop_registrations WHERE student_id = ? AND laptop_serial = ?");
            $stmt->execute([$student_id, $laptop_serial]);
            $registration = $stmt->fetch();

            if ($registration) {
                if ($registration['status'] === 'not stolen') {
                    echo json_encode([
                        'success' => true,
                        'message' => "Verification successful: Laptop (Serial: {$laptop_serial}) belongs to student {$student['full_name']} and is NOT STOLEN."
                    ]);
                } else if ($registration['status'] === 'stolen') {
                    echo json_encode([
                        'success' => false,
                        'message' => "Verification failed: Laptop (Serial: {$laptop_serial}) is REGISTERED but REPORTED STOLEN. Click 'Cease Laptop' to record.",
                        'reason_ceased' => 'Reported stolen in system',
                        'student' => $student, // Pass student info for cease action
                        'laptop' => $registration // Pass laptop info for cease action
                    ]);
                }
            } else {
                // Laptop serial does not match any 'not stolen' registration for this student
                // Attempt to get laptop model if it exists in another registration for better logging
                $model_stmt = $conn->prepare("SELECT laptop_model FROM laptop_registrations WHERE laptop_serial = ? LIMIT 1");
                $model_stmt->execute([$laptop_serial]);
                $found_model = $model_stmt->fetchColumn() ?: 'Unknown';

                $owner_message = "";
                $owner_stmt = $conn->prepare("SELECT us.full_name FROM laptop_registrations lr JOIN university_students us ON lr.student_id = us.id WHERE lr.laptop_serial = ? LIMIT 1");
                $owner_stmt->execute([$laptop_serial]);
                $actual_owner = $owner_stmt->fetchColumn();

                if ($actual_owner) {
                    $owner_message = " It belongs to {$actual_owner}.";
                }

                echo json_encode([
                    'success' => false,
                    'message' => "Verification failed: Laptop (Serial: {$laptop_serial}) is NOT REGISTERED to student {$student['full_name']} or status is incorrect." . $owner_message . " Click 'Cease Laptop' to record.",
                    'reason_ceased' => 'Serial mismatch or not registered to student',
                    'student' => $student, // Pass student info for cease action
                    'laptop' => ['laptop_serial' => $laptop_serial, 'laptop_model' => $found_model] // Pass laptop info for cease action
                ]);
            }

        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error during verification.', 'reason_ceased' => '']);
        }

    } else if ($action === 'cease') {
        $student_id = trim($_POST['student_id']);
        $laptop_serial = trim($_POST['laptop_serial']);
        $reason_ceased = trim($_POST['reason_ceased']);
        $laptop_model = trim($_POST['laptop_model'] ?? 'Unknown'); // From original registration or guessed

        if (empty($student_id) || empty($laptop_serial) || empty($reason_ceased)) {
            echo json_encode(['success' => false, 'message' => 'Missing data for ceasing laptop.']);
            exit();
        }

        try {
            // Insert into ceased_laptops
            $insert_ceased = $conn->prepare("INSERT INTO ceased_laptops (student_id, laptop_serial, laptop_model, reason_ceased) VALUES (?, ?, ?, ?)");
            if ($insert_ceased->execute([
                $student_id,
                $laptop_serial,
                $laptop_model,
                $reason_ceased
            ])) {
                echo json_encode(['success' => true, 'message' => 'Laptop successfully ceased and recorded.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to record ceased laptop.']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error during ceasing: ' . $e->getMessage()]);
        }
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
?> 