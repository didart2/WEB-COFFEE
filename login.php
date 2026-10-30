<?php
session_start();
include 'db_connect.php';

$error = ''; // Variable to store error messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Fetch the user based on username and role
    $query = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $query->bind_param("ss", $username, $role);
    $query->execute();
    $result = $query->get_result();

    // Validate credentials
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Check password
        if ($password === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $role;
            $_SESSION['username'] = $user['username'];

            // Redirect based on role
            if ($role == 'teacher') {
                header("Location: teacher.php");
            } elseif ($role == 'student') {
                header("Location: student.php");
            } elseif ($role == 'admin') {
                header("Location: admin.php");  // Redirect to admin dashboard
            } elseif ($role == 'registrar') {
                header("Location: registrar.php");  // Redirect to registrar dashboard
            }
            exit; // Ensure script terminates after redirection
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Invalid username or role!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>

<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #f1f3f5;
}

.signup-container {
    width: 500px;
    border: 2px solidrgb(42, 44, 42);
    border-radius: 15px;
    background-color: #ffffff;
    padding: 30px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
}

.signup-container::before {
    content: "";
    background: url('image.png') no-repeat center;
    background-size: contain;
    opacity: 1.15;
    position: absolute;
    top: 20px;
    left: 20px;
    right: 20px;
    bottom: 0;
    z-index: -1;
}

.header {
    text-align: center;
    margin-bottom: 30px;
}

.header img {
    width: 250px;
    height: 80px;
}

.header h1 {
    font-size: 26px;
    color:rgb(17, 43, 17);
    margin-bottom: 8px;
    font-weight: bold;
}

.header p {
    font-size: 17px;
    color: #777;
}

.signup-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.signup-form input[type="text"],
.signup-form input[type="password"],
.signup-form select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 10px;
    font-size: 16px;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.signup-form input[type="text"]:focus,
.signup-form input[type="password"]:focus,
.signup-form select:focus {
    border-color: #006400;
    box-shadow: 0 0 8px rgba(0, 100, 0, 0.3);
}

.signup-form button {
    padding: 12px;
    background-color: #006400;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s ease;
}

.signup-form button:hover {
    background-color: #004d26;
    transform: translateY(-2px);
}

.login-link {
    text-align: center;
    margin-top: 20px;
}

.login-link a {
    color: #006400;
    text-decoration: none;
    font-size: 15px;
}

.login-link a:hover {
    text-decoration: underline;
}

.error {
    color: #ff4d4d;
    text-align: center;
    margin-bottom: 15px;
    font-size: 14px;
}

</style>
   
</head>
<body>
    <div class="signup-container">
        <div class="header">
            <img src="image/phinma.png" alt="PHINMA">
            <h1>PHINMA EDUCATION</h1>
            <p>Making lives better through education</p>
        </div>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form class="signup-form" method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" placeholder="Enter your username" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>

            <label for="role">Role:</label>
            <select name="role" id="role" required>
                <option value="" disabled selected>Select your role</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
                <option value="admin">Admin</option>  <!-- Added Admin -->
                <option value="registrar">Registrar</option>  <!-- Added Registrar -->
            </select>
            <button type="submit">Log In</button>
        </form>
        <div class="login-link">
            <p>Don't have an account? <a href="sign_up.php">Sign up here</a></p>
        </div>
    </div>
</body>
</html>
