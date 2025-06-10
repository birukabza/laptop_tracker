<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laptop Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
            padding: 20px;
        }
        header {
            background: #35424a;
            color: #ffffff;
            padding: 10px 0;
            text-align: center;
        }
        footer {
            background: #35424a;
            color: #ffffff;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin: 10px 0;
        }
        ul li a {
            text-decoration: none;
            color: #35424a;
            font-weight: bold;
        }
        ul li a:hover {
            color: #e8491d;
        }
    </style>
</head>
<body>
    <header>
        <h1>Welcome to Laptop Tracker</h1>
    </header>
    <div class="container">
        <p>Please select a portal to continue:</p>
        <ul>
            <li><a href="admin/login.php">Admin Portal</a></li>
            <li><a href="guard/login.php">Guard Portal</a></li>
            <li><a href="student-portal/login.php">Student Portal</a></li>
        </ul>
    </div>
    <footer>
        <p>&copy; 2023 Laptop Tracker. All rights reserved.</p>
    </footer>
</body>
</html> 