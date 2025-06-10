<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "laptop_sentinel";

try {
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $database";
    $conn->exec($sql);
    
    // Select the database
    $conn->exec("USE $database");
    
    // Create admin table
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id VARCHAR(20) PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    
    // Create university students table (dummy data for verification)
    $sql = "CREATE TABLE IF NOT EXISTS university_students (
        id VARCHAR(20) PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        department VARCHAR(100) NOT NULL,
        year_of_study INT NOT NULL,
        phone VARCHAR(20) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Create laptop registrations table
    $sql = "CREATE TABLE IF NOT EXISTS laptop_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        laptop_serial VARCHAR(100) NOT NULL,
        laptop_model VARCHAR(100) NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('stolen', 'not stolen') DEFAULT 'not stolen',
        FOREIGN KEY (student_id) REFERENCES university_students(id),
        UNIQUE KEY unique_serial (laptop_serial)
    )";
    $conn->exec($sql);

    // Create guards table
    $sql = "CREATE TABLE IF NOT EXISTS guards (
        id VARCHAR(20) PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Insert dummy guard data if not exists
    $stmt = $conn->prepare("INSERT IGNORE INTO guards (id, password) VALUES (?, ?)");
    $stmt->execute(['grd/1234/14', password_hash('guardpass', PASSWORD_DEFAULT)]);

    // Create ceased laptops table
    $sql = "CREATE TABLE IF NOT EXISTS ceased_laptops (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        laptop_serial VARCHAR(100) NOT NULL,
        laptop_model VARCHAR(100) NOT NULL,
        reason_ceased VARCHAR(255) NOT NULL,
        ceased_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('ceased', 'returned') DEFAULT 'ceased'
    )";
    $conn->exec($sql);

    // Create student_users table
    $sql = "CREATE TABLE IF NOT EXISTS student_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id VARCHAR(20) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES university_students(id)
    )";
    $conn->exec($sql);

    // Create reported_laptops table
    $sql = "CREATE TABLE IF NOT EXISTS reported_laptops (
        report_id INT PRIMARY KEY AUTO_INCREMENT,
        laptop_id INT NOT NULL,
        student_id VARCHAR(20) NOT NULL,
        report_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('reported', 'found') DEFAULT 'reported',
        found_date DATETIME,
        FOREIGN KEY (laptop_id) REFERENCES laptop_registrations(id),
        FOREIGN KEY (student_id) REFERENCES university_students(id)
    )";
    $conn->exec($sql);

    // Insert dummy university student data if not exists
    $dummy_students = [
        ['UGR/1234/15', 'Biruk Geremew', 'Computer Science', 3, '0911223344'],
        ['UGR/1235/15', 'Dagemawi Bekele', 'Information Technology', 2, '0922334455'],
        ['UGR/1236/15', 'Fitsum Ferdu', 'Computer Science', 4, '0933445566'],
        ['UGR/1237/15', 'Dagem Shiferaw', 'Information Technology', 3, '0944556677'],
        ['UGR/1238/15', 'Abebe Kebede', 'Computer Science', 2, '0955667788']
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO university_students (id, full_name, department, year_of_study, phone) VALUES (?, ?, ?, ?, ?)");
    foreach ($dummy_students as $student) {
        $stmt->execute($student);
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 