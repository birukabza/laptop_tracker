<?php
session_start();
require_once '../config/db_connect.php';

// Check if guard is logged in
if (!isset($_SESSION['guard_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch ceased laptops
try {
    $ceased_stmt = $conn->query("SELECT * FROM ceased_laptops ORDER BY ceased_date DESC");
    $ceased_laptops = $ceased_stmt->fetchAll();
} catch(PDOException $e) {
    $ceased_error = "Error fetching ceased laptops: " . $e->getMessage();
}

// Fetch reported stolen laptops
try {
    $stolen_stmt = $conn->query("
        SELECT lr.*, us.full_name, us.department 
        FROM laptop_registrations lr 
        JOIN university_students us ON lr.student_id = us.id 
        WHERE lr.status = 'stolen' 
        ORDER BY lr.registration_date DESC
    ");
    $stolen_laptops = $stolen_stmt->fetchAll();
} catch(PDOException $e) {
    $stolen_error = "Error fetching stolen laptops: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Dashboard - Laptop Sentinel</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
</head>
<body>
    <div class="container">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1 class="dashboard-title">Guard Panel</h1>
                </div>
                <div class="header-right">
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab-button active" onclick="openTab(event, 'gateVerification')">Gate Verification</button>
                <button class="tab-button" onclick="openTab(event, 'ceasedLaptops')">Ceased Laptops</button>
                <button class="tab-button" onclick="openTab(event, 'reportedStolen')">Reported Stolen</button>
                <button class="tab-button" onclick="openTab(event, 'studentLaptopInfo')">Student & Laptop Info</button>
            </div>

            <div id="gateVerification" class="tab-content active">
                <h2>Gate Verification</h2>
                <form id="gateVerificationForm" action="#" method="POST">
                    <div class="form-group">
                        <label for="gv_student_id">Student ID:</label>
                        <input type="text" id="gv_student_id" name="student_id" placeholder="UGR/1234/15" required>
                        <div id="gvStudentInfo" class="student-info"></div>
                    </div>
                    <div class="form-group">
                        <label for="gv_laptop_serial">Laptop Serial Number:</label>
                        <input type="text" id="gv_laptop_serial" name="laptop_serial" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Verify Laptop</button>
                </form>
                <div id="verificationResults" class="status-message-container" style="margin-top: 1rem;"></div>
            </div>

            <div id="ceasedLaptops" class="tab-content">
                <h2>Ceased Laptops</h2>
                <?php if(isset($ceased_error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($ceased_error); ?></div>
                <?php elseif(empty($ceased_laptops)): ?>
                    <p>No laptops currently ceased.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student ID</th>
                                    <th>Laptop Serial</th>
                                    <th>Laptop Model</th>
                                    <th>Reason Ceased</th>
                                    <th>Ceased Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($ceased_laptops as $laptop): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($laptop['id']); ?></td>
                                    <td><?php echo htmlspecialchars(urldecode($laptop['student_id'])); ?></td>
                                    <td><?php echo htmlspecialchars($laptop['laptop_serial']); ?></td>
                                    <td><?php echo htmlspecialchars($laptop['laptop_model']); ?></td>
                                    <td><?php echo htmlspecialchars(urldecode($laptop['reason_ceased'])); ?></td>
                                    <td><?php echo htmlspecialchars($laptop['ceased_date']); ?></td>
                                    <td><span class="status-badge <?php echo htmlspecialchars(strtolower($laptop['status'])); ?>"><?php echo htmlspecialchars($laptop['status']); ?></span></td>
                                    <td>
                                        <?php if (strtolower($laptop['status']) === 'ceased'): ?>
                                            <button onclick="releaseCeasedLaptop(<?php echo $laptop['id']; ?>)" class="btn btn-success btn-sm">Release</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div id="reportedStolen" class="tab-content">
                <h2>Reported Stolen Laptops</h2>
                <?php if(isset($stolen_error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($stolen_error); ?></div>
                <?php elseif(empty($stolen_laptops)): ?>
                    <p>No laptops currently reported stolen.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Full Name</th>
                                    <th>Department</th>
                                    <th>Laptop Serial</th>
                                    <th>Laptop Model</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($stolen_laptops as $laptop): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(urldecode($laptop['student_id'])); ?></td>
                                    <td><?php echo htmlspecialchars($laptop['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($laptop['department']); ?></td>
                                    <td><?php echo htmlspecialchars($laptop['laptop_serial']); ?></td>
                                    <td><?php echo htmlspecialchars($laptop['laptop_model']); ?></td>
                                    <td><?php echo htmlspecialchars($laptop['registration_date']); ?></td>
                                    <td><span class="status-badge stolen"><?php echo htmlspecialchars($laptop['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Student & Laptop Info Tab -->
            <div id="studentLaptopInfo" class="tab-content">
                <h2>Student Information</h2>
                <form id="studentInfoForm" method="POST">
                    <div class="form-group">
                        <label for="si_student_id">Student ID:</label>
                        <input type="text" id="si_student_id" name="student_id" placeholder="UGR/1234/15" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Get Student Info</button>
                </form>
                <div id="studentInfoResults" class="status-message-container" style="margin-top: 1rem;"></div>

                <h2 style="margin-top: 2rem;">Laptop Information</h2>
                <form id="laptopInfoForm" method="POST">
                    <div class="form-group">
                        <label for="li_laptop_serial">Laptop Serial Number:</label>
                        <input type="text" id="li_laptop_serial" name="laptop_serial" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Get Laptop Info</button>
                </form>
                <div id="laptopInfoResults" class="status-message-container" style="margin-top: 1rem;"></div>
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

        // Default open tab
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.tab-button.active').click();
        });

        document.getElementById('gv_student_id').addEventListener('blur', function() {
            const studentId = this.value;
            const studentInfoDiv = document.getElementById('gvStudentInfo');
            if(studentId) {
                fetch(`../admin/check_student.php?id=${studentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if(data.exists) {
                            if(data.student.is_registered) {
                                studentInfoDiv.innerHTML = `
                                    <div class="success-message">
                                        Student found: ${data.student.full_name}<br>
                                        Department: ${data.student.department}<br>
                                        Year: ${data.student.year_of_study}<br>
                                        Current laptop registrations: ${data.laptop_count}/2 (not stolen)
                                    </div>
                                `;
                            } else {
                                studentInfoDiv.innerHTML = `
                                    <div class="error-message">
                                        ${data.message}
                                    </div>
                                `;
                            }
                        } else {
                            studentInfoDiv.innerHTML = '<div class="error-message">Student not found in university database</div>';
                        }
                    })
                    .catch(error => {
                        studentInfoDiv.innerHTML = '<div class="error-message">Error fetching student info.</div>';
                        console.error('Error:', error);
                    });
            } else {
                studentInfoDiv.innerHTML = '';
            }
        });

        // Handle Gate Verification Form Submission
        document.getElementById('gateVerificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const studentId = document.getElementById('gv_student_id').value;
            const laptopSerial = document.getElementById('gv_laptop_serial').value;
            const resultsDiv = document.getElementById('verificationResults');

            fetch('process_gate_check.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=verify&student_id=${encodeURIComponent(studentId)}&laptop_serial=${encodeURIComponent(laptopSerial)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultsDiv.innerHTML = `<div class="success-message">${data.message}</div>`;
                    resultsDiv.innerHTML += `<div class="modal-actions" style="justify-content: center; margin-top: 1rem;"><button onclick="clearVerificationForm()" class="btn btn-secondary">Clear Form</button></div>`;
                } else {
                    resultsDiv.innerHTML = `<div class="error-message">${data.message}</div>`;
                    resultsDiv.innerHTML += `
                        <div class="modal-actions" style="justify-content: center; margin-top: 1rem;">
                            <button onclick="ceaseLaptop(
                                '${encodeURIComponent(studentId)}',
                                '${encodeURIComponent(laptopSerial)}',
                                '${encodeURIComponent(data.reason_ceased)}',
                                '${encodeURIComponent(data.laptop ? data.laptop.laptop_model : 'Unknown')}'
                            )" class="btn btn-danger">Cease Laptop</button>
                            <button onclick="clearVerificationForm()" class="btn btn-secondary">Clear Form</button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = '<div class="error-message">An error occurred during verification.</div>';
                console.error('Error:', error);
            });
        });

        // AJAX for Student Info lookup in Student & Laptop Info tab
        document.getElementById('studentInfoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const studentId = document.getElementById('si_student_id').value;
            const resultsDiv = document.getElementById('studentInfoResults');
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
        document.getElementById('laptopInfoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const laptopSerial = document.getElementById('li_laptop_serial').value;
            const resultsDiv = document.getElementById('laptopInfoResults');
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

        // Clear verification form fields
        function clearVerificationForm() {
            document.getElementById('gv_student_id').value = '';
            document.getElementById('gv_laptop_serial').value = '';
            document.getElementById('gvStudentInfo').innerHTML = '';
            document.getElementById('verificationResults').innerHTML = '';
        }

        function ceaseLaptop(studentId, laptopSerial, reasonCeased, laptopModel) {
            fetch('process_gate_check.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=cease&student_id=${encodeURIComponent(studentId)}&laptop_serial=${encodeURIComponent(laptopSerial)}&reason_ceased=${encodeURIComponent(reasonCeased)}&laptop_model=${encodeURIComponent(laptopModel)}`
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('verificationResults');
                if (data.success) {
                    resultsDiv.innerHTML = `<div class="success-message">${data.message}</div>`;
                    alert(data.message);
                    window.location.reload();
                } else {
                    resultsDiv.innerHTML = `<div class="error-message">${data.message}</div>`;
                    alert(data.message);
                }
                clearVerificationForm();
            })
            .catch(error => {
                const resultsDiv = document.getElementById('verificationResults');
                resultsDiv.innerHTML = '<div class="error-message">An error occurred during ceasing.</div>';
                console.error('Error:', error);
            });
        }

        function releaseCeasedLaptop(ceasedId) {
            if (confirm('Are you sure you want to release this ceased laptop? This will mark it as returned.')) {
                fetch('process_ceased_laptop.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=release&id=${ceasedId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload(); // Reload to reflect changes
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while releasing the laptop.');
                });
            }
        }
    </script>
</body>
</html>