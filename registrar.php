<?php
session_start();
include 'db_connect.php';

// Check if the user is logged in as registrar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'registrar') {
    header("Location: login.php");
    exit;
}

// Fetch subjects
$subjects = [];
$subject_query = $conn->query("SELECT id, name FROM subjects");
while ($row = $subject_query->fetch_assoc()) {
    $subjects[] = $row;
}

// Create Subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subject_name'])) {
    $subject_name = $_POST['subject_name'];

    // Insert the new subject into the database
    $query = $conn->prepare("INSERT INTO subjects (name) VALUES (?)");
    $query->bind_param("s", $subject_name);
    if ($query->execute()) {
        echo "<div class='alert alert-success'>Subject created successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error creating subject: " . $conn->error . "</div>";
    }
}

// Register Teacher
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['teacher_name'], $_POST['teacher_username'], $_POST['teacher_password'], $_POST['subject_id'])) {
    $teacher_name = $_POST['teacher_name'];
    $teacher_username = $_POST['teacher_username'];
    $teacher_password = password_hash($_POST['teacher_password'], PASSWORD_DEFAULT); // Hash password
    $subject_id = $_POST['subject_id'];

    // Insert teacher data into the database
    $query = $conn->prepare("INSERT INTO users (fullname, username, password, role, subject) VALUES (?, ?, ?, 'teacher', ?)");
    $query->bind_param("sssi", $teacher_name, $teacher_username, $teacher_password, $subject_id);

    if ($query->execute()) {
        echo "<div class='alert alert-success'>Teacher registered successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error registering teacher: " . $conn->error . "</div>";
    }
}

// Register Student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'], $_POST['student_name'], $_POST['student_username'], $_POST['student_password'])) {
    $student_id = $_POST['student_id'];
    $student_name = $_POST['student_name'];
    $student_username = $_POST['student_username'];
    $student_password = password_hash($_POST['student_password'], PASSWORD_DEFAULT); // Hash password

    // Insert student data into the database
    $query = $conn->prepare("INSERT INTO users (id, fullname, username, password, role) VALUES (?, ?, ?, ?, 'student')");
    $query->bind_param("isss", $student_id, $student_name, $student_username, $student_password);

    if ($query->execute()) {
        echo "<div class='alert alert-success'>Student registered successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error registering student: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrar Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Registrar Dashboard</h2>

        <!-- Create Subject -->
        <h3>Create Subject</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="subject_name" class="form-label">Subject Name</label>
                <input type="text" class="form-control" id="subject_name" name="subject_name" required>
            </div>
            <button type="submit" class="btn btn-success">Create Subject</button>
        </form>

        <hr>

        <!-- Register Teacher -->
        <h3>Register Teacher</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="teacher_name" class="form-label">Teacher Name</label>
                <input type="text" class="form-control" id="teacher_name" name="teacher_name" required>
            </div>
            <div class="mb-3">
                <label for="teacher_username" class="form-label">Username</label>
                <input type="text" class="form-control" id="teacher_username" name="teacher_username" required>
            </div>
            <div class="mb-3">
                <label for="teacher_password" class="form-label">Password</label>
                <input type="password" class="form-control" id="teacher_password" name="teacher_password" required>
            </div>
            <div class="mb-3">
                <label for="subject_id" class="form-label">Assign Subject</label>
                <select class="form-control" id="subject_id" name="subject_id" required>
                    <?php foreach ($subjects as $subject) : ?>
                        <option value="<?= $subject['id'] ?>"><?= $subject['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Register Teacher</button>
        </form>

        <hr>

        <!-- Register Student -->
        <h3>Register Student</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="number" class="form-control" id="student_id" name="student_id" required>
            </div>
            <div class="mb-3">
                <label for="student_name" class="form-label">Student Name</label>
                <input type="text" class="form-control" id="student_name" name="student_name" required>
            </div>
            <div class="mb-3">
                <label for="student_username" class="form-label">Username</label>
                <input type="text" class="form-control" id="student_username" name="student_username" required>
            </div>
            <div class="mb-3">
                <label for="student_password" class="form-label">Password</label>
                <input type="password" class="form-control" id="student_password" name="student_password" required>
            </div>
            <button type="submit" class="btn btn-warning">Register Student</button>
        </form>
    </div>
</body>
</html>
