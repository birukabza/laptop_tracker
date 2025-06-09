<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all laptop registrations with student details
try {
    $stmt = $conn->query("
        SELECT lr.*, us.full_name, us.department, us.year_of_study 
        FROM laptop_registrations lr 
        JOIN university_students us ON lr.student_id = us.id 
        ORDER BY lr.registration_date DESC
    ");
    $registrations = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error fetching registrations: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Laptop Sentinel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1 class="dashboard-title">Laptop Registration Management</h1>
                </div>
                <div class="header-right">
                    <button class="btn btn-success" onclick="showAddRegistrationModal()">Register New Laptop</button>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>

            <?php if(isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Laptop Serial</th>
                            <th>Laptop Model</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($registrations as $reg): ?>
                        <tr class="<?php echo $reg['status'] === 'inactive' ? 'inactive-row' : ''; ?>">
                            <td><?php echo htmlspecialchars($reg['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['department']); ?></td>
                            <td><?php echo htmlspecialchars($reg['year_of_study']); ?></td>
                            <td><?php echo htmlspecialchars($reg['laptop_serial']); ?></td>
                            <td><?php echo htmlspecialchars($reg['laptop_model']); ?></td>
                            <td><?php echo htmlspecialchars($reg['registration_date']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $reg['status'] === 'stolen' ? 'stolen' : 'not-stolen'; ?>">
                                    <?php echo htmlspecialchars($reg['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_registration.php?id=<?php echo $reg['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <button onclick="showDeleteModal(<?php echo $reg['id']; ?>, '<?php echo htmlspecialchars($reg['laptop_serial']); ?>', '<?php echo htmlspecialchars($reg['laptop_model']); ?>')" class="btn btn-danger btn-sm">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Registration Modal -->
    <div id="addRegistrationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Register New Laptop</h2>
            <form id="addRegistrationForm" action="process_registration.php" method="POST">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" placeholder="UGR/1234/15" required>
                    <div id="studentInfo" class="student-info"></div>
                </div>
                <div class="form-group">
                    <label for="laptop_serial">Laptop Serial Number</label>
                    <input type="text" id="laptop_serial" name="laptop_serial" required>
                </div>
                <div class="form-group">
                    <label for="laptop_model">Laptop Model</label>
                    <input type="text" id="laptop_model" name="laptop_model" required>
                </div>
                <div class="form-group">
                    <label for="add_status">Status:</label>
                    <select id="add_status" name="status" required>
                        <option value="not stolen">Not Stolen</option>
                        <option value="stolen">Stolen</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Register Laptop</button>
                    <button type="button" class="btn btn-secondary" onclick="hideAddRegistrationModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2>Delete Registration</h2>
            <p>Are you sure you want to permanently delete this laptop registration?</p>
            <div class="student-info">
                <p><strong>Laptop Serial:</strong> <span id="deleteSerial"></span></p>
                <p><strong>Laptop Model:</strong> <span id="deleteModel"></span></p>
            </div>
            <div class="modal-actions">
                <button onclick="hideDeleteModal()" class="btn btn-secondary">Cancel</button>
                <button onclick="deleteRegistration()" class="btn btn-danger">Delete Registration</button>
            </div>
        </div>
    </div>

    <script>
        let registrationToDelete = null;

        function showAddRegistrationModal() {
            document.getElementById('addRegistrationModal').style.display = 'block';
        }

        function hideAddRegistrationModal() {
            document.getElementById('addRegistrationModal').style.display = 'none';
        }

        function showDeleteModal(id, serial, model) {
            registrationToDelete = id;
            document.getElementById('deleteSerial').textContent = serial;
            document.getElementById('deleteModel').textContent = model;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            registrationToDelete = null;
        }

        function deleteRegistration() {
            if (registrationToDelete) {
                window.location.href = `process_registration.php?action=delete&id=${registrationToDelete}`;
            }
        }

        // Check student ID and laptop count when entering student ID
        document.getElementById('student_id').addEventListener('blur', function() {
            const studentId = this.value;
            if(studentId) {
                fetch(`check_student.php?id=${studentId}`)
                    .then(response => response.json())
                    .then(data => {
                        const studentInfo = document.getElementById('studentInfo');
                        if(data.exists) {
                            studentInfo.innerHTML = `
                                <div class="success-message">
                                    Student found: ${data.student.full_name}<br>
                                    Department: ${data.student.department}<br>
                                    Year: ${data.student.year_of_study}<br>
                                    Current laptop registrations: ${data.laptop_count}/2
                                </div>
                            `;
                            if(data.laptop_count >= 2) {
                                studentInfo.innerHTML += '<div class="error-message">This student has reached the maximum number of laptop registrations (2)</div>';
                            }
                        } else {
                            studentInfo.innerHTML = '<div class="error-message">Student not found in university database</div>';
                        }
                    });
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('deleteModal')) {
                hideDeleteModal();
            }
        }
    </script>
</body>
</html> 