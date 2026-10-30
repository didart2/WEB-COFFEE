<?php
session_start();
include 'db_connect.php';

// Ensure the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Logged-in student's ID

// Fetch the user's full name and profile image from the database
$name_query = $conn->prepare("SELECT fullname, profile_image FROM users WHERE id = ?");
$name_query->bind_param("i", $user_id);
$name_query->execute();
$name_result = $name_query->get_result();
$user = $name_result->fetch_assoc();

// Ensure variables are set properly
$fullname = htmlspecialchars($user['fullname']);
$profile_image = $user['profile_image'] ?? ''; // Set default empty string

$upload_dir = "uploads/"; // Ensure this folder exists

// Handle Profile Image Upload (only if the user has uploaded a new image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));

    if (in_array($file_extension, $allowed_extensions)) {
        $new_file_name = uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $new_file_name;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            // Update database
            $update_query = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $update_query->bind_param("si", $new_file_name, $user_id);
            $update_query->execute();

            // Set the new profile image
            $profile_image = $new_file_name;
        }
    }
}

// Determine the profile image path
$profile_image_url = "uploads/default.png"; // Default image
if (!empty($profile_image) && file_exists($upload_dir . $profile_image)) {
    $profile_image_url = $upload_dir . $profile_image;
}

// Query to fetch CS and PE along with grades
$query = $conn->prepare("SELECT grades.p1_grade, grades.p2_grade, grades.p3_grade, grades.final_grade, 
                                users.fullname AS teacher_name, grades.subject,
                                grades.p1_cs, grades.p1_pe, grades.p2_cs, grades.p2_pe, grades.p3_cs, grades.p3_pe
                         FROM grades
                         JOIN users ON grades.teacher_id = users.id
                         WHERE grades.student_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* Base Reset & Typography */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Roboto', sans-serif;
    }
    
    body {
      background: url('uploads/coc-highlights.jpg') no-repeat center center fixed; /* Use your image as the background */
      background-size: cover;
      padding: 20px;
      position: relative;
    }

    .overlay {
      position: absolute;  /* Changed to absolute to place it over the body */
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.5); /* Reduced opacity for softer fade */
      z-index: -1;
    }

    .dashboard-container {
      max-width: 1000px;
      margin: 30px auto;
      padding: 40px;
      background-color: rgba(255, 255, 255, 0.9); /* Transparent white */
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      position: relative;
      z-index: 1;
    }
    
    /* Header */
    .header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .header h1 {
      margin-top: 15px;
      font-size: 28px;
      color: #333;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    /* Enhanced Table Styling */
    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin-top: 30px;
      background-color: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }

    thead {
      background: linear-gradient(90deg, #00703e, #004d26);
      color: #fff;
    }

    th, td {
      text-align: center;
      padding: 16px;
      font-size: 16px;
      border-bottom: 1px solid #e0e0e0;
    }

    th {
      font-weight: bold;
      letter-spacing: 1px;
    }

    tbody tr {
      transition: background-color 0.3s ease;
    }

    tbody tr:hover {
  background-color: #99cdaa; /* Pastel Green */
  box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
  transition: 0.3s ease-in-out;
}


    tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tbody tr:first-child td:first-child {
      border-top-left-radius: 12px;
    }

    tbody tr:first-child td:last-child {
      border-top-right-radius: 12px;
    }

    tbody tr:last-child td:first-child {
      border-bottom-left-radius: 12px;
    }

    tbody tr:last-child td:last-child {
      border-bottom-right-radius: 12px;
    }

    /* Final Grade Section */
    .final-grade {
      background-color: #004d26;
      color: #fff;
      padding: 15px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }

    /* Logout Button */
.logout-link {
  display: inline-block;
  margin-top: 20px;
  padding: 10px 20px;
  font-size: 16px;
  color: #fff;
  background-color: #004d26;
  text-decoration: none;
  border-radius: 6px;
  transition: background-color 0.3s ease-in-out;
}

.logout-link:hover {
  background-color: #00371a;
}

    /* Responsive Design */
    @media (max-width: 768px) {
      .header h1 {
        font-size: 24px;
      }

      table th, table td {
        font-size: 14px;
        padding: 12px;
      }

      .final-grade {
        font-size: 14px;
        padding: 12px;
      }

      .logout {
        font-size: 14px;
        padding: 10px 20px;
      }
    }

  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="header">
      <h1>Hi, <?= $fullname ?></h1>
    </div>

    <?php while ($grade = $result->fetch_assoc()): ?>
      <div style="margin-bottom: 40px;">
        <h2 style="font-size: 22px;"><?= htmlspecialchars($grade['subject']) ?></h2>
        <h3 style="font-size: 18px;">Teacher: <?= htmlspecialchars($grade['teacher_name']) ?></h3>

        <table>
          <thead>
            <tr>
              <th>Period</th>
              <th>Grade</th>
              <th>CS</th>
              <th>PE</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Period 1</td>
              <td><?= htmlspecialchars($grade['p1_grade']) ?></td>
              <td><?= htmlspecialchars($grade['p1_cs']) ?></td>
              <td><?= htmlspecialchars($grade['p1_pe']) ?></td>
            </tr>
            <tr>
              <td>Period 2</td>
              <td><?= htmlspecialchars($grade['p2_grade']) ?></td>
              <td><?= htmlspecialchars($grade['p2_cs']) ?></td>
              <td><?= htmlspecialchars($grade['p2_pe']) ?></td>
            </tr>
            <tr>
              <td>Period 3</td>
              <td><?= htmlspecialchars($grade['p3_grade']) ?></td>
              <td><?= htmlspecialchars($grade['p3_cs']) ?></td>
              <td><?= htmlspecialchars($grade['p3_pe']) ?></td>
            </tr>
          </tbody>
        </table>

        <div class="final-grade" style="background-color: <?= (float)$grade['final_grade'] >= 50 ? 'rgb(117, 240, 117)' : '#2222'; ?>;">
          <span>Final Grade:</span>
          <span style="color: <?= (float)$grade['final_grade'] >= 50 ? 'blue' : 'red'; ?>;">
            <?= htmlspecialchars($grade['final_grade']) ?> (<?= (float)$grade['final_grade'] >= 50 ? 'Pass' : 'Fail' ?>)
          </span>
        </div>
        <hr style="border: 0; border-top: 1px solid #ddd; margin: 40px 0;">
      </div>
    <?php endwhile; ?>

    <div style="text-align: center;">
      <a href="logout.php" class="logout-link">Logout</a>
    </div>
  </div>
</body>
</html>
