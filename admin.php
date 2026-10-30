<?php
session_start();
include 'db_connect.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Assign subject to teacher or student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'], $_POST['subject_id'])) {
    $user_id = $_POST['user_id'];
    $subject_id = $_POST['subject_id'];
    
    // Update the user with the assigned subject
    $query = $conn->prepare("UPDATE users SET subject_id = ? WHERE id = ?");
    $query->bind_param("ii", $subject_id, $user_id);
    
    if ($query->execute()) {
        echo "Subject assigned successfully!";
    } else {
        echo "Error assigning subject: " . $conn->error;
    }
}

// Fetch teachers and students for the dropdown
$teachers_query = $conn->prepare("SELECT id, fullname FROM users WHERE role = 'teacher'");
$teachers_query->execute();
$teachers = $teachers_query->get_result();

$students_query = $conn->prepare("SELECT id, fullname FROM users WHERE role = 'student'");
$students_query->execute();
$students = $students_query->get_result();

// Fetch all subjects
$subjects_query = $conn->prepare("SELECT id, name FROM subjects");
$subjects_query->execute();
$subjects = $subjects_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Admin Dashboard</h2>
        
        <!-- Assign Subject to Teacher -->
        <h3>Assign Subject to Teacher</h3>
        <form method="POST" action="admin_dashboard.php">
            <div class="mb-3">
                <label for="teacher_id" class="form-label">Select Teacher</label>
                <select class="form-control" id="teacher_id" name="user_id" required>
                    <option value="">-- Select Teacher --</option>
                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                        <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['fullname']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="subject_id" class="form-label">Select Subject</label>
                <select class="form-control" id="subject_id" name="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                        <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Assign Subject</button>
        </form>

        <!-- Assign Subject to Student -->
        <h3 class="mt-4">Assign Subject to Student</h3>
        <form method="POST" action="admin_dashboard.php">
            <div class="mb-3">
                <label for="student_id" class="form-label">Select Student</label>
                <select class="form-control" id="student_id" name="user_id" required>
                    <option value="">-- Select Student --</option>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['fullname']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="subject_id" class="form-label">Select Subject</label>
                <select class="form-control" id="subject_id" name="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                        <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Assign Subject</button>
        </form>
    </div>
</body>
</html>
