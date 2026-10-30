<?php
session_start();
include 'db_connect.php';

// Check if teacher is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

// Fetch the teacher's full name and subject
$teacher_id = $_SESSION['user_id'];
$teacher_query = $conn->prepare("SELECT fullname, subject FROM users WHERE id = ?");
$teacher_query->bind_param("i", $teacher_id);
$teacher_query->execute();
$teacher_result = $teacher_query->get_result();

if ($teacher_result->num_rows > 0) {
    $teacher_data = $teacher_result->fetch_assoc();
    $teacher_fullname = $teacher_data['fullname'];
    $teacher_subject = $teacher_data['subject'];
} else {
    $teacher_fullname = "Teacher";
    $teacher_subject = "N/A";
}

// Fetch students for the Grade Calculator form
$students_query = $conn->prepare("SELECT id, fullname FROM users WHERE role = 'student'");
$students_query->execute();
$students = $students_query->get_result();

// Fetch graded students by the teacher
$graded_students_query = $conn->prepare("SELECT g.id, u.fullname, g.subject, 
                                                 g.p1_grade, g.p1_cs, g.p1_pe, 
                                                 g.p2_grade, g.p2_cs, g.p2_pe, 
                                                 g.p3_grade, g.p3_cs, g.p3_pe, 
                                                 g.final_grade 
                                          FROM grades g 
                                          JOIN users u ON g.student_id = u.id 
                                          WHERE g.teacher_id = ?");
$graded_students_query->bind_param("i", $teacher_id);
$graded_students_query->execute();
$graded_students = $graded_students_query->get_result();

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'], $_POST['p1_grade'], $_POST['p1_cs'], $_POST['p1_pe'], 
                                                   $_POST['p2_grade'], $_POST['p2_cs'], $_POST['p2_pe'], 
                                                   $_POST['p3_grade'], $_POST['p3_cs'], $_POST['p3_pe'])) {

    $student_id = $_POST['student_id'];
    $p1_grade = $_POST['p1_grade'];
    $p1_cs = $_POST['p1_cs'];
    $p1_pe = $_POST['p1_pe'];
    $p2_grade = $_POST['p2_grade'];
    $p2_cs = $_POST['p2_cs'];
    $p2_pe = $_POST['p2_pe'];
    $p3_grade = $_POST['p3_grade'];
    $p3_cs = $_POST['p3_cs'];
    $p3_pe = $_POST['p3_pe'];

    // Calculate final grade as the average of period grades
    $final_grade = ($p1_grade + $p2_grade + $p3_grade) / 3;

    $query = $conn->prepare("INSERT INTO grades (student_id, teacher_id, subject, 
                                                  p1_grade, p1_cs, p1_pe, 
                                                  p2_grade, p2_cs, p2_pe, 
                                                  p3_grade, p3_cs, p3_pe, final_grade) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    // Adjust bind_param types as needed (example uses "i" for student_id/teacher_id, "s" for subject, etc.)
    $query->bind_param("iisssssssdddi", $student_id, $teacher_id, $teacher_subject, 
                                      $p1_grade, $p1_cs, $p1_pe, 
                                      $p2_grade, $p2_cs, $p2_pe, 
                                      $p3_grade, $p3_cs, $p3_pe, $final_grade);
    if ($query->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Error inserting grades: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Teacher Dashboard</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
 /* Base Reset & Typography */
/* Dashboard Container */
.dashboard-container {
  width: 100%;
  border: 2px solid #004d26;
  border-radius: 10px;
  background-color: #ffffff;
  padding: 20px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  position: relative;
}
.header h1 {
  font-size: 24px;
  color:rgb(0, 0, 0);
}

.header h2 {
  font-size: 18px;
  color:rgb(0, 0, 0);
  margin-top: 4px;
}

/* Form Styling */
.grade-calculator-form {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.grade-calculator-form select,
.grade-calculator-form input[type="text"] {
  width: 100%;
  padding: 5px;
  border: 1px solid #ccc;
  border-radius: 3px;
  font-size: 13px;
}


.grade-calculator-form button:hover {
  background-color:rgb(41, 46, 44);
}

/* Card Styling */
.card {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 15px;
}
/* Logout Button */
.logout-link {
  display: inline-block;
  padding: 10px 20px;
  font-size: 16px;
  color: #fff;
  background-color:rgb(41, 49, 45);
  text-decoration: none;
  border-radius: 6px;
  margin-top: 20px;
}

.logout-link:hover {
  background-color:rgb(0, 0, 0);
}

  </style>
<body>
<div class="dashboard-container">
    <div class="header">
        <!-- Optional: Add an image logo here -->
        <h1>Welcome, <?= htmlspecialchars($teacher_fullname) ?></h1>
        <h2>Subject: <?= htmlspecialchars($teacher_subject) ?></h2>
    </div>
    
    <!-- Grade Calculator Form -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        Grade Calculator
      </div>
      <div class="card-body">
        <form class="grade-calculator-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
          <!-- Select Student -->
          <div>
            <label for="student_id" class="form-label">Select Student:</label>
            <select name="student_id" id="student_id" required>
              <option value="">-- Select Student --</option>
              <?php 
              // Re-run the query to get the list of students.
              $students_query = $conn->prepare("SELECT id, fullname FROM users WHERE role = 'student'");
              $students_query->execute();
              $students = $students_query->get_result();
              while ($student = $students->fetch_assoc()): ?>
                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['fullname']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Period 1 Card -->
          <div class="row">
            <div class="col-md-4">
              <div class="card mb-3">
                <div class="card-header bg-secondary text-black">Period 1</div>
                <div class="card-body">
                  <div class="mb-2">
                    <label class="form-label">Class Standing:</label>
                    <div id="quizContainerP1"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addQuizInput(1)">Add Quiz</button>
                  </div>
                   <div class="mb-2">
                    <label class="form-label">Attendance:</label>
                    <input type="text" id="attendanceP1" placeholder="Attended/Total">
                  </div>
                  <button type="button" class="btn btn-sm btn-info mb-2" onclick="computeCS(1)">Compute CS</button>
                  <p id="csResultP1" class="result"></p>
                  <input type="hidden" id="p1CS" name="p1_cs">
                  <div class="mb-2">
                    <label class="form-label">Period Exam:</label>
                    <input type="text" id="examP1" placeholder="Score/Total">
                  </div>
                  <button type="button" class="btn btn-sm btn-info mb-2" onclick="computeExam(1)">Compute PE</button>
                  <p id="examResultP1" class="result"></p>
                  <input type="hidden" id="p1Exam" name="p1_pe">
                  <button type="button" class="btn btn-sm btn-warning mb-2" onclick="calculatePeriod(1)">Compute P1 Grade</button>
                  <p id="resultP1" class="result"></p>
                  <input type="hidden" id="p1Value" name="p1_grade">
                </div>
              </div>
            </div>

            <!-- Period 2 Card -->
            <div class="col-md-4">
              <div class="card mb-3">
                <div class="card-header bg-secondary text-black">Period 2</div>
                <div class="card-body">
                  <div class="mb-2">
                    <label class="form-label">Class Standing:</label>
                    <div id="quizContainerP2"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addQuizInput(2)">Add Quiz</button>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Attendance:</label>
                    <input type="text" id="attendanceP2" placeholder="Attended/Total">
                  </div>
                  <button type="button" class="btn btn-sm btn-info mb-2" onclick="computeCS(2)">Compute CS</button>
                  <p id="csResultP2" class="result"></p>
                  <input type="hidden" id="p2CS" name="p2_cs">
                  <div class="mb-2">
                    <label class="form-label">Period Exam:</label>
                    <input type="text" id="examP2" placeholder="Score/Total">
                  </div>
                  <button type="button" class="btn btn-sm btn-info mb-2" onclick="computeExam(2)">Compute PE</button>
                  <p id="examResultP2" class="result"></p>
                  <input type="hidden" id="p2Exam" name="p2_pe">
                  <button type="button" class="btn btn-sm btn-warning mb-2" onclick="calculatePeriod(2)">Compute P2 Grade</button>
                  <p id="resultP2" class="result"></p>
                  <input type="hidden" id="p2Value" name="p2_grade">
                </div>
              </div>
            </div>

            <!-- Period 3 Card -->
            <div class="col-md-4">
              <div class="card mb-3">
                <div class="card-header bg-secondary text-black">Period 3</div>
                <div class="card-body">
                  <div class="mb-2">
                    <label class="form-label">Class Standing:</label>
                    <div id="quizContainerP3"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addQuizInput(3)">Add Quiz</button>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Attendance:</label>
                    <input type="text" id="attendanceP3" placeholder="Attended/Total">
                  </div>
                  <button type="button" class="btn btn-sm btn-info mb-2" onclick="computeCS(3)">Compute CS</button>
                  <p id="csResultP3" class="result"></p>
                  <input type="hidden" id="p3CS" name="p3_cs">
                  <div class="mb-2">
                    <label class="form-label">Period Exam:</label>
                    <input type="text" id="examP3" placeholder="Score/Total">
                  </div>
                  <button type="button" class="btn btn-sm btn-info mb-2" onclick="computeExam(3)">Compute PE</button>
                  <p id="examResultP3" class="result"></p>
                  <input type="hidden" id="p3Exam" name="p3_pe">
                  <button type="button" class="btn btn-sm btn-warning mb-2" onclick="calculatePeriod(3)">Compute P3 Grade</button>
                  <p id="resultP3" class="result"></p>
                  <input type="hidden" id="p3Value" name="p3_grade">
                </div>
              </div>
            </div>
          </div>

          <!-- Final Grade Calculation -->
          <div class="text-center my-3">
            <button type="button" class="btn btn-outline-dark" onclick="calculateFinalGrade()">Compute Final Grade</button>
            <p id="finalResult" class="result"></p>
          </div>

          <div class="text-center">
            <button type="submit" class="btn btn-primary">Submit Grades</button>
          </div>
        </form>
      </div>
    </div>

<!-- Graded Students Table -->
<div class="card mt-4">
    <div class="card-header bg-primary text-white">
        Graded Students
    </div>
    <div class="card-body">
        <?php if ($graded_students->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Student Name</th>
                            <th>Subject</th>
                            <th>Period 1<br>(Grade | CS | PE)</th>
                            <th>Period 2<br>(Grade | CS | PE)</th>
                            <th>Period 3<br>(Grade | CS | PE)</th>
                            <th>Final Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $graded_students->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td>
                                    <?= isset($row['p1_grade']) ? number_format($row['p1_grade'], 2) : 'N/A' ?><br>
                                    (CS: <?= isset($row['p1_cs']) ? number_format($row['p1_cs'], 2) : '0.00' ?>,
                                    PE: <?= isset($row['p1_pe']) ? number_format($row['p1_pe'], 2) : '0.00' ?>)
                                    <br>
                                    <?= (isset($row['p1_grade']) && $row['p1_grade'] >= 50) ? 'Pass' : 'Fail' ?>
                                </td>
                                <td>
                                    <?= isset($row['p2_grade']) ? number_format($row['p2_grade'], 2) : 'N/A' ?><br>
                                    (CS: <?= isset($row['p2_cs']) ? number_format($row['p2_cs'], 2) : '0.00' ?>,
                                    PE: <?= isset($row['p2_pe']) ? number_format($row['p2_pe'], 2) : '0.00' ?>)
                                    <br>
                                    <?= (isset($row['p2_grade']) && $row['p2_grade'] >= 50) ? 'Pass' : 'Fail' ?>
                                </td>
                                <td>
                                    <?= isset($row['p3_grade']) ? number_format($row['p3_grade'], 2) : 'N/A' ?><br>
                                    (CS: <?= isset($row['p3_cs']) ? number_format($row['p3_cs'], 2) : '0.00' ?>,
                                    PE: <?= isset($row['p3_pe']) ? number_format($row['p3_pe'], 2) : '0.00' ?>)
                                    <br>
                                    <?= (isset($row['p3_grade']) && $row['p3_grade'] >= 50) ? 'Pass' : 'Fail' ?>
                                </td>
                                <td>
                                    <?php
                                    $final_grade = isset($row['final_grade']) ? number_format($row['final_grade'], 2) : 'N/A';
                                    $pass_fail_class = (isset($row['final_grade']) && $row['final_grade'] >= 50) ? 'text-success' : 'text-danger';
                                    $pass_fail_text = (isset($row['final_grade']) && $row['final_grade'] >= 50) ? 'Pass' : 'Fail';
                                    ?>
                                    <span class="<?= $pass_fail_class ?>"><?= $final_grade ?></span><br>
                                    <span class="<?= $pass_fail_class ?>"><?= $pass_fail_text ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">No graded students found.</p>
        <?php endif; ?>
    </div>
</div>



    <!-- Logout Link -->
    <div class="text-center mt-4">
      <a href="logout.php" class="logout-link">Logout</a>
    </div>
</div>

<!-- JavaScript Functions for Grade Calculator -->
<script>
  // Add Quiz Input for Class Standing Calculation
  function addQuizInput(period) {
    let quizContainer = document.getElementById('quizContainerP' + period);
    let quizItem = document.createElement("div");
    quizItem.classList.add("quiz-item");
    let input = document.createElement("input");
    input.type = "text";
    input.placeholder = "Score/Total";
    input.className = "form-control form-control-sm my-1";
    let removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "btn btn-danger btn-sm";
    removeButton.innerHTML = "✖";
    removeButton.onclick = function () { quizItem.remove(); };
    quizItem.appendChild(input);
    quizItem.appendChild(removeButton);
    quizContainer.appendChild(quizItem);
  }

  // Compute Class Standing (CS)
  function computeCS(period) {
    let quizzes = document.querySelectorAll('#quizContainerP' + period + ' input');
    let totalScore = 0, totalMax = 0;
    quizzes.forEach(quiz => {
      let parts = quiz.value.split("/");
      if (parts.length === 2) {
        let score = parseFloat(parts[0]) || 0;
        let max = parseFloat(parts[1]) || 0;
        totalScore += score;
        totalMax += max;
      }
    });
    // Assume quizzes count for 50% and attendance for 10%
    let quizPercentage = totalMax > 0 ? (totalScore / totalMax) * 50 : 0;
    let attendanceField = document.getElementById('attendanceP' + period);
    let attendanceParts = attendanceField.value.split("/");
    let attendanceScore = parseFloat(attendanceParts[0]) || 0;
    let attendanceTotal = parseFloat(attendanceParts[1]) || 1;
    let attendancePercentage = (attendanceScore / attendanceTotal) * 10;
    let cs = quizPercentage + attendancePercentage;
    document.getElementById('csResultP' + period).innerHTML = 'P' + period + '-CS: ' + cs.toFixed(2);
    document.getElementById('p' + period + 'CS').value = cs.toFixed(2);
  }

  // Compute Period Exam (PE)
  function computeExam(period) {
    let examField = document.getElementById('examP' + period);
    let parts = examField.value.split("/");
    let score = parseFloat(parts[0]) || 0;
    let total = parseFloat(parts[1]) || 1;
    let pe = (score / total) * 40; // Exams count for 40%
    document.getElementById('examResultP' + period).innerHTML = 'P' + period + '-PE: ' + pe.toFixed(2);
    document.getElementById('p' + period + 'Exam').value = pe.toFixed(2);
  }

  // Compute Final Grade for a Period (CS + PE)
  function calculatePeriod(period) {
    let cs = parseFloat(document.getElementById('p' + period + 'CS').value) || 0;
    let pe = parseFloat(document.getElementById('p' + period + 'Exam').value) || 0;
    let periodGrade = cs + pe;
    document.getElementById('resultP' + period).innerHTML = 'P' + period + ' Grade: ' + periodGrade.toFixed(2);
    document.getElementById('p' + period + 'Value').value = periodGrade.toFixed(2);
  }

  // Compute Final Grade across all periods (Average)
  function calculateFinalGrade() {
    let p1 = parseFloat(document.getElementById('p1Value').value) || 0;
    let p2 = parseFloat(document.getElementById('p2Value').value) || 0;
    let p3 = parseFloat(document.getElementById('p3Value').value) || 0;
    let finalGrade = (p1 + p2 + p3) / 3;
    document.getElementById('finalResult').innerText = 'Final Grade: ' + finalGrade.toFixed(2);
  }
</script>

<!-- Bootstrap Bundle JS (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
