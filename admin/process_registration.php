<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $registration_id = (int)$_GET['id'];
    
    // Delete the registration
    $stmt = $conn->prepare("DELETE FROM laptop_registrations WHERE id = ?");
    
    if ($stmt->execute([$registration_id])) {
        $_SESSION['success'] = "Laptop registration deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting laptop registration.";
    }
    
    header('Location: dashboard.php');
    exit();
}

// Handle registration deletion and restoration
if (isset($_GET['action']) && isset($_GET['id'])) {
    try {
        if ($_GET['action'] === 'restore') {
            // Check if student has reached laptop limit before restoring
            $stmt = $conn->prepare("
                SELECT student_id 
                FROM laptop_registrations 
                WHERE id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $registration = $stmt->fetch();
            
            if ($registration) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) 
                    FROM laptop_registrations 
                    WHERE student_id = ? AND status = 'active'
                ");
                $stmt->execute([$registration['student_id']]);
                $active_count = $stmt->fetchColumn();
                
                if ($active_count >= 2) {
                    header("Location: dashboard.php?error=Cannot restore: Student has reached maximum laptop limit (2)");
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE laptop_registrations SET status = 'active' WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                header("Location: dashboard.php?success=Registration restored successfully");
            } else {
                header("Location: dashboard.php?error=Registration not found");
            }
        }
        exit();
    } catch(PDOException $e) {
        header("Location: dashboard.php?error=Database error");
        exit();
    }
}

// Handle new registration and updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $laptop_serial = trim($_POST['laptop_serial']);
    $laptop_model = trim($_POST['laptop_model']);
    $status = isset($_POST['status']) && in_array($_POST['status'], ['stolen', 'not stolen', 'ceased']) ? $_POST['status'] : 'not stolen';
    $registration_id = isset($_POST['registration_id']) ? (int)$_POST['registration_id'] : 0;

    // Validate input
    if (empty($student_id) || empty($laptop_serial) || empty($laptop_model)) {
        $_SESSION['error'] = "All fields are required.";
        header('Location: ' . ($registration_id ? "edit_registration.php?id=$registration_id" : "dashboard.php"));
        exit();
    }

    // Check if student exists in student_users table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM student_users WHERE student_id = ?");
    $stmt->execute([$student_id]);
    if ($stmt->fetchColumn() == 0) {
        $_SESSION['error'] = "Student with ID $student_id is not registered in the system. Please ask the student to sign up first.";
        header('Location: ' . ($registration_id ? "edit_registration.php?id=$registration_id" : "dashboard.php"));
        exit();
    }

    if ($registration_id) {
        // Update existing registration
        // Check if the new serial number is already registered to another laptop (excluding the current one)
        $stmt = $conn->prepare("SELECT id FROM laptop_registrations WHERE laptop_serial = ? AND id != ?");
        $stmt->execute([$laptop_serial, $registration_id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Laptop with serial number '$laptop_serial' is already registered to another laptop.";
            header("Location: edit_registration.php?id=$registration_id");
            exit();
        }

        $stmt = $conn->prepare("UPDATE laptop_registrations SET student_id = ?, laptop_serial = ?, laptop_model = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$student_id, $laptop_serial, $laptop_model, $status, $registration_id])) {
            $_SESSION['success'] = "Laptop registration updated successfully.";
        } else {
            $_SESSION['error'] = "Error updating laptop registration.";
        }
    } else {
        // New registration
        // Check if laptop serial number already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM laptop_registrations WHERE laptop_serial = ?");
        $stmt->execute([$laptop_serial]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Laptop with serial number '$laptop_serial' is already registered.";
            header('Location: dashboard.php');
            exit();
        }

        // Check student's current laptop count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM laptop_registrations WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $current_laptop_count = $stmt->fetchColumn();

        if ($current_laptop_count >= 2) {
            $_SESSION['error'] = "Student with ID $student_id has already registered the maximum of 2 laptops.";
            header('Location: dashboard.php');
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO laptop_registrations (student_id, laptop_serial, laptop_model, status) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$student_id, $laptop_serial, $laptop_model, $status])) {
            $_SESSION['success'] = "Laptop registration added successfully.";
        } else {
            $_SESSION['error'] = "Error adding laptop registration.";
        }
    }

    header('Location: dashboard.php');
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?> 