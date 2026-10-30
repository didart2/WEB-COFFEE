<?php
session_start();
include 'db_connect.php';

$error = ''; // Variable to store error messages

// Fetch subjects from the database (used for teacher signup)
$subjects = [];
$subjects_query = $conn->prepare("SELECT id, name FROM subjects");
$subjects_query->execute();
$result = $subjects_query->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Initialize variables
    $subject_name = null;
    $imagePath = "default.png"; // Default profile image

    // For teachers, retrieve the selected subject
    if ($role === 'teacher' && isset($_POST['subject']) && !empty($_POST['subject'])) {
        $subject_id = $_POST['subject'];

        // Fetch subject name from the subjects table
        $subject_query = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
        $subject_query->bind_param("i", $subject_id);
        $subject_query->execute();
        $subject_result = $subject_query->get_result();

        if ($subject_result->num_rows > 0) {
            $subject_row = $subject_result->fetch_assoc();
            $subject_name = $subject_row['name'];
        } else {
            $error = "Invalid subject selected.";
        }
    } elseif ($role === 'teacher') {
        $error = "Please select a subject for the teacher role.";
    }

    // Validate required fields
    if (empty($fullname) || empty($username) || empty($password) || empty($role)) {
        $error = "All fields are required!";
    }

    // Check if username already exists
    if (empty($error)) {
        $check_query = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $check_query->bind_param("s", $username);
        $check_query->execute();
        $check_result = $check_query->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Username already exists!";
        }
    }

    // Handle image upload
    if (empty($error) && isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
        $target_dir = "uploads/";
        $unique_name = uniqid() . '_' . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $unique_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image file
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check === false) {
            $error = "File is not a valid image.";
        } elseif ($_FILES["profile_image"]["size"] > 2000000) {
            $error = "File size must be 2MB or less.";
        } elseif (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
            $error = "Only JPG, JPEG, and PNG files are allowed.";
        } else {
            // Move the file to the uploads folder
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $imagePath = $unique_name;
            } else {
                $error = "Failed to upload image.";
            }
        }
    }

    // If no errors, insert user into database
    if (empty($error)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($role === 'teacher') {
            $insert_query = $conn->prepare("INSERT INTO users (fullname, username, password, role, subject, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_query->bind_param("ssssss", $fullname, $username, $hashed_password, $role, $subject_name, $imagePath);
        } else {
            $insert_query = $conn->prepare("INSERT INTO users (fullname, username, password, role, profile_image) VALUES (?, ?, ?, ?, ?)");
            $insert_query->bind_param("sssss", $fullname, $username, $hashed_password, $role, $imagePath);
        }

        if ($insert_query->execute()) {
            // Redirect to login page after successful signup
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            header("Location: login.php");
            exit;
        } else {
            $error = "Failed to register. Please try again.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up Page</title>
  <style>
    /* Your existing CSS */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background-color: #f5f5f5;
    }

    .signup-container {
      width: 500px;
      border: 2px solid #004d26;
      border-radius: 10px;
      background-color: #ffffff;
      padding: 20px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .signup-container::before {
      content: "";
      background: url('image.png') no-repeat center;
      background-size: contain;
      opacity: 0.1;
      position: absolute;
      top: 20px;
      left: 20px;
      right: 20px;
      bottom: 0;
      z-index: -1;
    }

    .header {
      text-align: center;
      margin-bottom: 20px;
    }

    .header img {
      width: 80px;
      margin-bottom: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      border-radius: 50%;
    }

    .header h1 {
      font-size: 24px;
      color: #004d26;
    }

    .header p {
      font-size: 14px;
      color: #555;
    }

    .signup-form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .signup-form input[type="text"],
    .signup-form input[type="password"],
    .signup-form select,
    .signup-form input[type="file"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }

    .signup-form button {
      padding: 10px;
      background-color: #004d26;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
    }

    .signup-form button:hover {
      background-color: #00703e;
    }

    .login-link {
      text-align: center;
      margin-top: 10px;
    }

    .login-link a {
      color: #004d26;
      text-decoration: none;
      font-size: 14px;
    }

    .login-link a:hover {
      text-decoration: underline;
    }

    .error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
      font-size: 14px;
    }
  </style>
  <script>
    // This function shows or hides the subject selection based on the selected role.
    function toggleSubjectSelection() {
      const roleSelect = document.getElementById('role');
      const subjectField = document.getElementById('subjectField');
      if (roleSelect.value === 'teacher') {
        subjectField.style.display = 'block';
      } else {
        subjectField.style.display = 'none';
      }
    }

    window.addEventListener('DOMContentLoaded', () => {
      // Initialize the visibility when the page loads
      toggleSubjectSelection();

      // Add event listener for role select changes
      document.getElementById('role').addEventListener('change', toggleSubjectSelection);
    });
  </script>
</head>
<body>
  <div class="signup-container">
    <div class="header">
      <img src="uploads/coc.jpg" alt="Logo">
      <h1>PHINMA EDUCATION</h1>
      <p>Making lives better through education</p>
    </div>
    <?php if (!empty($error)): ?>
      <p class="error"><?= htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form class="signup-form" method="POST" enctype="multipart/form-data">
      <label for="fullname">Full Name:</label>
      <input type="text" name="fullname" id="fullname" placeholder="Enter your full name" required>

      <label for="username">Username:</label>
      <input type="text" name="username" id="username" placeholder="Choose a username" required>

      <label for="password">Password:</label>
      <input type="password" name="password" id="password" placeholder="Create a password" required>

      <label for="role">Role:</label>
      <select name="role" id="role" required>
        <option value="" disabled selected>Select your role</option>
        <option value="teacher" <?php if(isset($_POST['role']) && $_POST['role'] === 'teacher') echo 'selected'; ?>>Teacher</option>
        <option value="student" <?php if(isset($_POST['role']) && $_POST['role'] === 'student') echo 'selected'; ?>>Student</option>
      </select>

      <!-- Subject Selection: This will only show for Teacher role -->
      <div id="subjectField" style="display: none;">
        <label for="subject">Subject:</label>
        <select name="subject" id="subject">
          <option value="" disabled selected>Select your subject</option>
          <?php foreach ($subjects as $subject): ?>
            <option value="<?= $subject['id'] ?>" <?php if(isset($_POST['subject']) && $_POST['subject'] == $subject['id']) echo 'selected'; ?>>
              <?= htmlspecialchars($subject['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Image Upload -->
      <label for="profile_image">Profile Picture:</label>
      <input type="file" name="profile_image" id="profile_image" accept="image/*">

      <button type="submit">Sign Up</button>
    </form>
    <div class="login-link">
      <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
  </div>
</body>
</html>
