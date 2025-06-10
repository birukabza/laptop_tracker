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

            <div class="tabs">
                <button class="tab-button active" onclick="openTab(event, 'registrations')">Laptop Registrations</button>
                <button class="tab-button" onclick="openTab(event, 'studentLaptopInfo')">Student & Laptop Info</button>
            </div>

            <div id="registrations" class="tab-content active">
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

            <!-- Student & Laptop Info Tab -->
            <div id="studentLaptopInfo" class="tab-content">
                <h2>Student Information</h2>
                <form id="adminStudentInfoForm" method="POST">
                    <div class="form-group">
                        <label for="asi_student_id">Student ID:</label>
                        <input type="text" id="asi_student_id" name="student_id" placeholder="UGR/1234/15" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Get Student Info</button>
                </form>
                <div id="adminStudentInfoResults" class="status-message-container" style="margin-top: 1rem;"></div>

                <h2 style="margin-top: 2rem;">Laptop Information</h2>
                <form id="adminLaptopInfoForm" method="POST">
                    <div class="form-group">
                        <label for="ali_laptop_serial">Laptop Serial Number:</label>
                        <input type="text" id="ali_laptop_serial" name="laptop_serial" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Get Laptop Info</button>
                </form>
                <div id="adminLaptopInfoResults" class="status-message-container" style="margin-top: 1rem;"></div>
            </div>

        </div>
    </div>

    <!-- Add Registration Modal -->
    <div id="addRegistrationModal" class="modal">
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
            </form>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="hideAddRegistrationModal()">Cancel</button>
                <button type="submit" form="addRegistrationForm" class="btn btn-primary">Register Laptop</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2>Delete Registration</h2>
            <div class="delete-info-display">
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
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        let registrationToDelete = null;

        function showAddRegistrationModal() {
            document.getElementById('addRegistrationModal').style.display = 'block';
            // Re-enable form fields when opening the modal
            document.getElementById('laptop_serial').disabled = false;
            document.getElementById('laptop_model').disabled = false;
            document.getElementById('add_status').disabled = false;
            // Clear previous student info and input values
            document.getElementById('studentInfo').innerHTML = '';
            document.getElementById('student_id').value = '';
            document.getElementById('laptop_serial').value = '';
            document.getElementById('laptop_model').value = '';
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

        // Default open tab
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.tab-button.active').click();
        });

        // Check student ID and laptop count when entering student ID for registration
        document.getElementById('student_id').addEventListener('blur', function() {
            const studentId = this.value;
            if(studentId) {
                fetch(`check_student.php?id=${studentId}`)
                    .then(response => response.json())
                    .then(data => {
                        const studentInfoDiv = document.getElementById('studentInfo');
                        if(data.exists) {
                            if (data.student.is_registered) {
                                studentInfoDiv.innerHTML = `
                                <div class="success-message">
                                    Student found: ${data.student.full_name}<br>
                                        Email: ${data.student.email}<br>
                                    Department: ${data.student.department}<br>
                                    Year: ${data.student.year_of_study}<br>
                                        Phone: ${data.student.phone ? data.student.phone : 'N/A'}<br>
                                        Current laptop registrations: ${data.laptop_count}/2 (not stolen)
                                    </div>
                                `;
                                // Enable the form fields if student is registered and valid
                                document.getElementById('laptop_serial').disabled = false;
                                document.getElementById('laptop_model').disabled = false;
                                document.getElementById('add_status').disabled = false;
                            } else {
                                studentInfoDiv.innerHTML = `
                                    <div class="error-message">
                                        ${data.message}<br>
                                        Student details:<br>
                                        Name: ${data.student.full_name}<br>
                                        Department: ${data.student.department}<br>
                                        Year: ${data.student.year_of_study}
                                </div>
                            `;
                                // Disable the form if student is not registered
                                document.getElementById('laptop_serial').disabled = true;
                                document.getElementById('laptop_model').disabled = true;
                                document.getElementById('add_status').disabled = true;
                            }
                        } else {
                            studentInfoDiv.innerHTML = '<div class="error-message">Student not found in university database</div>';
                            // Disable the form if student is not found
                            document.getElementById('laptop_serial').disabled = true;
                            document.getElementById('laptop_model').disabled = true;
                            document.getElementById('add_status').disabled = true;
                        }
                    });
            } else {
                // If student ID is cleared, reset info and enable fields
                document.getElementById('studentInfo').innerHTML = '';
                document.getElementById('laptop_serial').disabled = false;
                document.getElementById('laptop_model').disabled = false;
                document.getElementById('add_status').disabled = false;
            }
        });

        // AJAX for Student Info lookup in Student & Laptop Info tab
        document.getElementById('adminStudentInfoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const studentId = document.getElementById('asi_student_id').value;
            const resultsDiv = document.getElementById('adminStudentInfoResults');
            resultsDiv.innerHTML = ''; // Clear previous results

            if (studentId) {
                fetch('get_info.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `student_id=${encodeURIComponent(studentId)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '<div class="success-message student-info-display"><h3>Student Details:</h3>';
                        html += `<p><strong>ID:</strong> ${data.student.id}</p>`;
                        html += `<p><strong>Full Name:</strong> ${data.student.full_name}</p>`;
                        html += `<p><strong>Department:</strong> ${data.student.department}</p>`;
                        html += `<p><strong>Year of Study:</strong> ${data.student.year_of_study}</p>`;
                        if (data.student.phone) {
                            html += `<p><strong>Phone:</strong> ${data.student.phone}</p>`;
                        }
                        if (data.student.email) {
                            html += `<p><strong>Email:</strong> ${data.student.email}</p>`;
                        }
                        if (data.student.user_created_at) {
                            html += `<p><strong>Account Created:</strong> ${data.student.user_created_at}</p>`;
                        }
                        html += '<h3>Registered Laptops:</h3>';
                        if (data.laptops.length > 0) {
                            html += '<table><thead><tr><th>Serial Number</th><th>Model</th><th>Registration Date</th><th>Status</th></tr></thead><tbody>';
                            data.laptops.forEach(laptop => {
                                html += `<tr>
                                    <td>${laptop.laptop_serial}</td>
                                    <td>${laptop.laptop_model}</td>
                                    <td>${laptop.registration_date}</td>
                                    <td><span class="status-badge ${laptop.status === 'stolen' ? 'stolen' : 'not-stolen'}">${laptop.status}</span></td>
                                </tr>`;
                            });
                            html += '</tbody></table>';
                        } else {
                            html += '<p>No laptops registered for this student.</p>';
                        }
                        html += '</div>';
                        resultsDiv.innerHTML = html;
                    } else {
                        resultsDiv.innerHTML = `<div class="error-message">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    resultsDiv.innerHTML = '<div class="error-message">An error occurred while fetching student info.</div>';
                    console.error('Error:', error);
                });
            } else {
                resultsDiv.innerHTML = '<div class="error-message">Please enter a Student ID.</div>';
            }
        });

        // AJAX for Laptop Info lookup in Student & Laptop Info tab
        document.getElementById('adminLaptopInfoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const laptopSerial = document.getElementById('ali_laptop_serial').value;
            const resultsDiv = document.getElementById('adminLaptopInfoResults');
            resultsDiv.innerHTML = ''; // Clear previous results

            if (laptopSerial) {
                fetch('get_info.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `laptop_serial=${encodeURIComponent(laptopSerial)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '<div class="success-message laptop-info-display"><h3>Laptop Details:</h3>';
                        html += `<p><strong>Serial Number:</strong> ${data.laptop.laptop_serial}</p>`;
                        html += `<p><strong>Model:</strong> ${data.laptop.laptop_model}</p>`;
                        html += `<p><strong>Registration Date:</strong> ${data.laptop.registration_date}</p>`;
                        html += `<p><strong>Status:</strong> <span class="status-badge ${data.laptop.status === 'stolen' ? 'stolen' : 'not-stolen'}">${data.laptop.status}</span></p>`;
                        
                        if (data.laptop.report_id) {
                            html += '<h4>Report Details:</h4>';
                            html += `<p><strong>Report Status:</strong> ${data.laptop.report_status}</p>`;
                            html += `<p><strong>Report Date:</strong> ${data.laptop.report_date}</p>`;
                            if (data.laptop.found_date) {
                                html += `<p><strong>Found Date:</strong> ${data.laptop.found_date}</p>`;
                            }
                        }

                        if (data.owner) {
                            html += '<h3>Owner Details:</h3>';
                            html += `<p><strong>ID:</strong> ${data.owner.id}</p>`;
                            html += `<p><strong>Full Name:</strong> ${data.owner.full_name}</p>`;
                            html += `<p><strong>Department:</strong> ${data.owner.department}</p>`;
                            html += `<p><strong>Year of Study:</strong> ${data.owner.year_of_study}</p>`;
                            if (data.owner.phone) {
                                html += `<p><strong>Phone:</strong> ${data.owner.phone}</p>`;
                            }
                            if (data.owner.email) {
                                html += `<p><strong>Email:</strong> ${data.owner.email}</p>`;
                            }
                        } else {
                            html += '<p>Owner details not found (possibly not registered to a student user).</p>';
                        }
                        html += '</div>';
                        resultsDiv.innerHTML = html;
                    } else {
                        resultsDiv.innerHTML = `<div class="error-message">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    resultsDiv.innerHTML = '<div class="error-message">An error occurred while fetching laptop info.</div>';
                    console.error('Error:', error);
                });
            } else {
                resultsDiv.innerHTML = '<div class="error-message">Please enter a Laptop Serial Number.</div>';
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('addRegistrationModal') || event.target == document.getElementById('deleteModal')) {
                document.getElementById('addRegistrationModal').style.display = 'none';
                document.getElementById('deleteModal').style.display = 'none';
            }
        }
    </script>
</body>
</html> 