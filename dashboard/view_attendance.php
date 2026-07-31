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
// 1️⃣ Fetch courses and sessions for dropdowns
// =============================================================
$courses = mysqli_query($conn, "SELECT id, code, title FROM course_details ORDER BY title");
$sessions = mysqli_query($conn, "SELECT id, year, term FROM session_details ORDER BY year DESC");

// =============================================================
// 2️⃣ Handle filter submission
// =============================================================
$attendance_records = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $session_id = $_POST['session_id'];
    $on_date = $_POST['on_date'];

    // Get faculty id
    $faculty_query = mysqli_query($conn, "SELECT id FROM faculty_details WHERE user_name='$faculty_user'");
    $faculty_row = mysqli_fetch_assoc($faculty_query);
    $faculty_id = $faculty_row['id'];

    // Fetch attendance records
    $sql = "
        SELECT s.roll_no, s.name, a.status, c.code, c.title, se.year, se.term
        FROM attendance_details a
        JOIN student_details s ON a.student_id = s.id
        JOIN course_details c ON a.course_id = c.id
        JOIN session_details se ON a.session_id = se.id
        WHERE a.faculty_id='$faculty_id'
        AND a.course_id='$course_id'
        AND a.session_id='$session_id'
        AND a.on_date='$on_date'
        ORDER BY s.roll_no ASC
    ";
    $attendance_records = mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Attendance</title>
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
        input[type='submit']:hover { background: darkblue; }
        select, input[type='date'] { padding: 8px; margin: 5px 0; width: 200px; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>

<h1>View Attendance</h1>
<p><a href="/attapp/dashboard/dashboard.php">⬅ Back to Dashboard</a></p>

<form method="POST" action="">
    <!-- Course Dropdown -->
    <label><strong>Select Course:</strong></label><br>
    <select name="course_id" required>
        <option value="">-- Choose Course --</option>
        <?php while ($c = mysqli_fetch_assoc($courses)) { ?>
            <option value="<?php echo $c['id']; ?>" <?php if(isset($course_id) && $course_id==$c['id']) echo "selected"; ?>>
                <?php echo htmlspecialchars($c['code'] . " - " . $c['title']); ?>
            </option>
        <?php } ?>
    </select><br><br>

    <!-- Session Dropdown -->
    <label><strong>Select Session:</strong></label><br>
    <select name="session_id" required>
        <option value="">-- Choose Session --</option>
        <?php while ($s = mysqli_fetch_assoc($sessions)) { ?>
            <option value="<?php echo $s['id']; ?>" <?php if(isset($session_id) && $session_id==$s['id']) echo "selected"; ?>>
                <?php echo htmlspecialchars($s['year'] . " - " . $s['term']); ?>
            </option>
        <?php } ?>
    </select><br><br>

    <!-- Date -->
    <label><strong>Date:</strong></label><br>
    <input type="date" name="on_date" value="<?php echo isset($on_date) ? $on_date : date('Y-m-d'); ?>" required><br><br>

    <input type="submit" value="View Attendance">
</form>

<?php if (!empty($attendance_records) && mysqli_num_rows($attendance_records) > 0): ?>
    <h2>Attendance Records</h2>
    <table>
        <tr>
            <th>Roll No</th>
            <th>Name</th>
            <th>Status</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($attendance_records)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
        <?php } ?>
    </table>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <p style="color:red;">No attendance records found for the selected course/session/date.</p>
<?php endif; ?>

</body>
</html>
