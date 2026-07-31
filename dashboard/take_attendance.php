<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['faculty'])) {
    header("Location: /attapp/login/index.php");
    exit();
}

// Include database connection
$path = $_SERVER['DOCUMENT_ROOT'];
require_once $path . "/attapp/database/database.php"; // $conn

$faculty_user = $_SESSION['faculty'];
$message = "";

// =============================================================
// ✅ 1. Fetch dropdown data (courses, sessions)
// =============================================================
$courses = mysqli_query($conn, "SELECT id, title, code FROM course_details ORDER BY title");
$sessions = mysqli_query($conn, "SELECT id, year, term FROM session_details ORDER BY year DESC");

// =============================================================
// ✅ 2. Handle attendance submission
// =============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $course_id = $_POST['course_id'];
    $session_id = $_POST['session_id'];
    $on_date = $_POST['on_date'];

    // Get faculty id
    $faculty_query = mysqli_query($conn, "SELECT id FROM faculty_details WHERE user_name='$faculty_user'");
    $faculty_row = mysqli_fetch_assoc($faculty_query);
    $faculty_id = $faculty_row['id'];

    // Insert attendance for each student
    foreach ($_POST['attendance'] as $student_id => $status) {
        $sql = "INSERT INTO attendance_details (faculty_id, course_id, session_id, student_id, on_date, status)
                VALUES ('$faculty_id', '$course_id', '$session_id', '$student_id', '$on_date', '$status')";

        // Prevent duplicate attendance for same date
        if (!mysqli_query($conn, $sql)) {
            if (strpos(mysqli_error($conn), 'Duplicate') !== false) {
                $message = "⚠️ Attendance already recorded for this date.";
            } else {
                $message = "❌ Error: " . mysqli_error($conn);
            }
        } else {
            $message = "✅ Attendance saved successfully!";
        }
    }
}

// =============================================================
// ✅ 3. Fetch all students
// =============================================================
$students = mysqli_query($conn, "SELECT id, roll_no, name FROM student_details ORDER BY roll_no ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Attendance</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f7f7f7; }
        h1 { color: #333; }
        form { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #eee; }
        input[type='submit'] {
            padding: 10px 20px;
            background: blue;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }
        input[type='submit']:hover {
            background: darkblue;
        }
        select, input[type='date'] {
            padding: 8px;
            margin: 5px 0;
            width: 200px;
        }
        .msg { margin: 10px 0; font-weight: bold; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>

<h1>Take Attendance</h1>
<p><a href="/attapp/dashboard/dashboard.php">⬅ Back to Dashboard</a></p>

<?php if ($message): ?>
    <p class="msg"><?php echo $message; ?></p>
<?php endif; ?>

<form method="POST" action="">
    <!-- Select Course -->
    <label><strong>Select Course:</strong></label><br>
    <select name="course_id" required>
        <option value="">-- Choose Course --</option>
        <?php while ($c = mysqli_fetch_assoc($courses)) { ?>
            <option value="<?php echo $c['id']; ?>">
                <?php echo htmlspecialchars($c['code'] . " - " . $c['title']); ?>
            </option>
        <?php } ?>
    </select><br><br>

    <!-- Select Session -->
    <label><strong>Select Session:</strong></label><br>
    <select name="session_id" required>
        <option value="">-- Choose Session --</option>
        <?php while ($s = mysqli_fetch_assoc($sessions)) { ?>
            <option value="<?php echo $s['id']; ?>">
                <?php echo htmlspecialchars($s['year'] . " - " . $s['term']); ?>
            </option>
        <?php } ?>
    </select><br><br>

    <!-- Date -->
    <label><strong>Date:</strong></label><br>
    <input type="date" name="on_date" value="<?php echo date('Y-m-d'); ?>" required><br><br>

    <!-- Students Table -->
    <table>
        <tr>
            <th>Roll No</th>
            <th>Name</th>
            <th>Status</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($students)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td>
                <label><input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="Present" required> Present</label>
                <label><input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="Absent"> Absent</label>
            </td>
        </tr>
        <?php } ?>
    </table>

    <input type="submit" value="Save Attendance">
</form>

</body>
</html>
