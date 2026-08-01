<?php
// ============================================================
// System Settings CRUD Page
// ============================================================
$path = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__, 1);
require_once $path . "/attapp/database/database.php";

session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: /attapp/login/index.php");
    exit();
}

$msg = "";
$msg_type = "";
$tab = $_GET['tab'] ?? 'faculty'; // Default tab

// ============================================================
// 1. Handle Deletions (GET)
// ============================================================
if (isset($_GET['del_faculty'])) {
    $id = (int)$_GET['del_faculty'];
    mysqli_query($conn, "DELETE FROM faculty_details WHERE id=$id");
    $msg = "Faculty deleted successfully.";
    $msg_type = "success";
    $tab = 'faculty';
} elseif (isset($_GET['del_session'])) {
    $id = (int)$_GET['del_session'];
    mysqli_query($conn, "DELETE FROM session_details WHERE id=$id");
    $msg = "Session deleted successfully.";
    $msg_type = "success";
    $tab = 'session';
} elseif (isset($_GET['del_course'])) {
    $id = (int)$_GET['del_course'];
    mysqli_query($conn, "DELETE FROM course_details WHERE id=$id");
    $msg = "Course deleted successfully.";
    $msg_type = "success";
    $tab = 'course';
}

// ============================================================
// 2. Handle Add/Update (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Faculty ---
    if (isset($_POST['save_faculty'])) {
        $id = (int)($_POST['f_id'] ?? 0);
        $user_name = mysqli_real_escape_string($conn, trim($_POST['user_name']));
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $password = mysqli_real_escape_string($conn, trim($_POST['password']));
        $tab = 'faculty';

        if ($id > 0) {
            $sql = "UPDATE faculty_details SET user_name='$user_name', name='$name', password='$password' WHERE id=$id";
            if (mysqli_query($conn, $sql)) {
                $msg = "Faculty updated successfully.";
                $msg_type = "success";
            } else {
                $msg = "Error updating faculty: " . mysqli_error($conn);
                $msg_type = "error";
            }
        } else {
            $sql = "INSERT INTO faculty_details (user_name, name, password) VALUES ('$user_name', '$name', '$password')";
            if (mysqli_query($conn, $sql)) {
                $msg = "Faculty added successfully.";
                $msg_type = "success";
            } else {
                $msg = "Error adding faculty: " . mysqli_error($conn);
                $msg_type = "error";
            }
        }
    }
    
    // --- Session ---
    elseif (isset($_POST['save_session'])) {
        $id = (int)($_POST['s_id'] ?? 0);
        $year = (int)$_POST['year'];
        $term = mysqli_real_escape_string($conn, trim($_POST['term']));
        $tab = 'session';

        if ($id > 0) {
            $sql = "UPDATE session_details SET year=$year, term='$term' WHERE id=$id";
            if (mysqli_query($conn, $sql)) {
                $msg = "Session updated successfully.";
                $msg_type = "success";
            } else {
                $msg = "Error updating session: " . mysqli_error($conn);
                $msg_type = "error";
            }
        } else {
            $sql = "INSERT INTO session_details (year, term) VALUES ($year, '$term')";
            if (mysqli_query($conn, $sql)) {
                $msg = "Session added successfully.";
                $msg_type = "success";
            } else {
                $msg = "Error adding session: " . mysqli_error($conn);
                $msg_type = "error";
            }
        }
    }

    // --- Course ---
    elseif (isset($_POST['save_course'])) {
        $id = (int)($_POST['c_id'] ?? 0);
        $code = mysqli_real_escape_string($conn, trim($_POST['code']));
        $title = mysqli_real_escape_string($conn, trim($_POST['title']));
        $credit = (int)$_POST['credit'];
        $tab = 'course';

        if ($id > 0) {
            $sql = "UPDATE course_details SET code='$code', title='$title', credit=$credit WHERE id=$id";
            if (mysqli_query($conn, $sql)) {
                $msg = "Course updated successfully.";
                $msg_type = "success";
            } else {
                $msg = "Error updating course: " . mysqli_error($conn);
                $msg_type = "error";
            }
        } else {
            $sql = "INSERT INTO course_details (code, title, credit) VALUES ('$code', '$title', $credit)";
            if (mysqli_query($conn, $sql)) {
                $msg = "Course added successfully.";
                $msg_type = "success";
            } else {
                $msg = "Error adding course: " . mysqli_error($conn);
                $msg_type = "error";
            }
        }
    }
}

// ============================================================
// 3. Fetch Data
// ============================================================
$faculty_res = mysqli_query($conn, "SELECT * FROM faculty_details ORDER BY name ASC");
$session_res = mysqli_query($conn, "SELECT * FROM session_details ORDER BY year DESC, term ASC");
$course_res  = mysqli_query($conn, "SELECT * FROM course_details ORDER BY code ASC");

// Include UI Header
require_once "header.php";
?>

<div class="page-header">
  <div class="page-breadcrumb">System <span>/</span> Settings</div>
  <h1 class="page-title">Settings Configuration</h1>
</div>

<?php if ($msg): ?>
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.875rem;font-weight:500;
  background:<?= $msg_type==='success'?'var(--present-bg)':'var(--absent-bg)' ?>;
  color:<?= $msg_type==='success'?'var(--present)':'var(--absent)' ?>;
  border:1px solid <?= $msg_type==='success'?'rgba(22,163,74,.25)':'rgba(220,38,38,.25)' ?>">
  <?= $msg_type==='success'?'✅':'❌' ?> <span><?= htmlspecialchars($msg) ?></span>
</div>
<?php endif; ?>

<!-- Tabs Navigation -->
<div style="display:flex;gap:4px;border-bottom:1px solid var(--border);margin-bottom:20px;overflow-x:auto;">
  <a href="?tab=faculty" class="btn <?= $tab === 'faculty' ? 'btn-secondary' : 'btn-ghost' ?>" style="border-bottom-left-radius:0;border-bottom-right-radius:0; <?= $tab === 'faculty' ? 'border-bottom-color:transparent;' : '' ?>">
    👤 Faculty Users
  </a>
  <a href="?tab=session" class="btn <?= $tab === 'session' ? 'btn-secondary' : 'btn-ghost' ?>" style="border-bottom-left-radius:0;border-bottom-right-radius:0; <?= $tab === 'session' ? 'border-bottom-color:transparent;' : '' ?>">
    📅 Academic Sessions
  </a>
  <a href="?tab=course" class="btn <?= $tab === 'course' ? 'btn-secondary' : 'btn-ghost' ?>" style="border-bottom-left-radius:0;border-bottom-right-radius:0; <?= $tab === 'course' ? 'border-bottom-color:transparent;' : '' ?>">
    📚 Courses
  </a>
</div>

<!-- ==================== FACULTY TAB ==================== -->
<?php if ($tab === 'faculty'): ?>
<div class="table-wrapper">
  <div class="table-toolbar">
    <div style="font-weight:700;font-size:1rem;color:var(--text);">Manage Faculty</div>
    <button class="btn btn-primary btn-sm" style="margin-left:auto" onclick="openFacultyModal()">
      Add Faculty
    </button>
  </div>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Username</th>
        <th style="width:100px;text-align:center">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (mysqli_num_rows($faculty_res) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($faculty_res)): ?>
        <tr>
          <td>#<?= $row['id'] ?></td>
          <td style="font-weight:600"><?= htmlspecialchars($row['name']) ?></td>
          <td style="color:var(--text-secondary)"><?= htmlspecialchars($row['user_name']) ?></td>
          <td>
            <div style="display:flex;justify-content:center;gap:6px">
              <button class="btn-icon-sm" title="Edit" onclick="editFaculty(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>', '<?= htmlspecialchars(addslashes($row['user_name'])) ?>', '<?= htmlspecialchars(addslashes($row['password'])) ?>')">
                <?= icon('edit') ?>
              </button>
              <a href="?tab=faculty&del_faculty=<?= $row['id'] ?>" class="btn-icon-sm" style="color:var(--absent)" title="Delete" onclick="return confirm('Delete this faculty member?')">
                <?= icon('trash') ?>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4" class="table-empty">No faculty members found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- ==================== SESSION TAB ==================== -->
<?php if ($tab === 'session'): ?>
<div class="table-wrapper">
  <div class="table-toolbar">
    <div style="font-weight:700;font-size:1rem;color:var(--text);">Manage Sessions</div>
    <button class="btn btn-primary btn-sm" style="margin-left:auto" onclick="openSessionModal()">
      Add Session
    </button>
  </div>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Year</th>
        <th>Term</th>
        <th style="width:100px;text-align:center">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (mysqli_num_rows($session_res) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($session_res)): ?>
        <tr>
          <td>#<?= $row['id'] ?></td>
          <td style="font-weight:600"><?= $row['year'] ?></td>
          <td>
            <span style="padding:4px 8px;background:var(--surface-2);border-radius:6px;font-size:.75rem;font-weight:600;">
              <?= htmlspecialchars($row['term']) ?>
            </span>
          </td>
          <td>
            <div style="display:flex;justify-content:center;gap:6px">
              <button class="btn-icon-sm" title="Edit" onclick="editSession(<?= $row['id'] ?>, <?= $row['year'] ?>, '<?= htmlspecialchars(addslashes($row['term'])) ?>')">
                <?= icon('edit') ?>
              </button>
              <a href="?tab=session&del_session=<?= $row['id'] ?>" class="btn-icon-sm" style="color:var(--absent)" title="Delete" onclick="return confirm('Delete this session?')">
                <?= icon('trash') ?>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4" class="table-empty">No sessions found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- ==================== COURSE TAB ==================== -->
<?php if ($tab === 'course'): ?>
<div class="table-wrapper">
  <div class="table-toolbar">
    <div style="font-weight:700;font-size:1rem;color:var(--text);">Manage Courses</div>
    <button class="btn btn-primary btn-sm" style="margin-left:auto" onclick="openCourseModal()">
      Add Course
    </button>
  </div>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Course Code</th>
        <th>Title</th>
        <th>Credits</th>
        <th style="width:100px;text-align:center">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (mysqli_num_rows($course_res) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($course_res)): ?>
        <tr>
          <td>#<?= $row['id'] ?></td>
          <td style="font-weight:600;color:var(--primary)"><?= htmlspecialchars($row['code']) ?></td>
          <td style="font-weight:500"><?= htmlspecialchars($row['title']) ?></td>
          <td><?= $row['credit'] ?></td>
          <td>
            <div style="display:flex;justify-content:center;gap:6px">
              <button class="btn-icon-sm" title="Edit" onclick="editCourse(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['code'])) ?>', '<?= htmlspecialchars(addslashes($row['title'])) ?>', <?= $row['credit'] ?>)">
                <?= icon('edit') ?>
              </button>
              <a href="?tab=course&del_course=<?= $row['id'] ?>" class="btn-icon-sm" style="color:var(--absent)" title="Delete" onclick="return confirm('Delete this course?')">
                <?= icon('trash') ?>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5" class="table-empty">No courses found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- ============================================================
     MODALS
     ============================================================ -->

<!-- Faculty Modal -->
<div class="modal-overlay" id="faculty-modal">
  <div class="modal-box" style="max-width:400px">
    <form method="POST" action="?tab=faculty">
      <div class="modal-header">
        <span class="modal-title" id="faculty-modal-title">Add Faculty</span>
        <button type="button" class="modal-close" onclick="closeModal('faculty-modal')">✕</button>
      </div>
      <div class="modal-body form-group">
        <input type="hidden" name="f_id" id="f_id" value="0">
        
        <label class="form-label">Full Name</label>
        <input type="text" name="name" id="f_name" class="form-control" required style="margin-bottom:12px">
        
        <label class="form-label">Username (Login ID)</label>
        <input type="text" name="user_name" id="f_user" class="form-control" required style="margin-bottom:12px">
        
        <label class="form-label">Password</label>
        <input type="text" name="password" id="f_pass" class="form-control" required>
      </div>
      <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn btn-cancel" onclick="closeModal('faculty-modal')">Cancel</button>
        <button type="submit" name="save_faculty" class="btn btn-grad btn-grad-green" id="faculty-btn-label">Save Faculty</button>
      </div>
    </form>
  </div>
</div>

<!-- Session Modal -->
<div class="modal-overlay" id="session-modal">
  <div class="modal-box" style="max-width:400px">
    <form method="POST" action="?tab=session">
      <div class="modal-header">
        <span class="modal-title" id="session-modal-title">Add Session</span>
        <button type="button" class="modal-close" onclick="closeModal('session-modal')">✕</button>
      </div>
      <div class="modal-body form-group">
        <input type="hidden" name="s_id" id="s_id" value="0">
        
        <label class="form-label">Year</label>
        <input type="number" name="year" id="s_year" class="form-control" required min="2000" max="2100" style="margin-bottom:12px" value="<?= date('Y') ?>">
        
        <label class="form-label">Term (e.g. Spring Semester)</label>
        <input type="text" name="term" id="s_term" class="form-control" required>
      </div>
      <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn btn-cancel" onclick="closeModal('session-modal')">Cancel</button>
        <button type="submit" name="save_session" class="btn btn-grad btn-grad-green" id="session-btn-label">Save Session</button>
      </div>
    </form>
  </div>
</div>

<!-- Course Modal -->
<div class="modal-overlay" id="course-modal">
  <div class="modal-box" style="max-width:400px">
    <form method="POST" action="?tab=course">
      <div class="modal-header">
        <span class="modal-title" id="course-modal-title">Add Course</span>
        <button type="button" class="modal-close" onclick="closeModal('course-modal')">✕</button>
      </div>
      <div class="modal-body form-group">
        <input type="hidden" name="c_id" id="c_id" value="0">
        
        <label class="form-label">Course Code (e.g. CS101)</label>
        <input type="text" name="code" id="c_code" class="form-control" required style="margin-bottom:12px">
        
        <label class="form-label">Course Title</label>
        <input type="text" name="title" id="c_title" class="form-control" required style="margin-bottom:12px">
        
        <label class="form-label">Credits</label>
        <input type="number" name="credit" id="c_credit" class="form-control" required min="1" max="10" value="3">
      </div>
      <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn btn-cancel" onclick="closeModal('course-modal')">Cancel</button>
        <button type="submit" name="save_course" class="btn btn-grad btn-grad-green" id="course-btn-label">Save Course</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Faculty
function openFacultyModal() {
    document.getElementById('f_id').value = '0';
    document.getElementById('f_name').value = '';
    document.getElementById('f_user').value = '';
    document.getElementById('f_pass').value = '';
    document.getElementById('faculty-modal-title').innerText = 'Add Faculty';
    document.getElementById('faculty-btn-label').innerText = 'Save Faculty';
    openModal('faculty-modal');
}
function editFaculty(id, name, user, pass) {
    document.getElementById('f_id').value = id;
    document.getElementById('f_name').value = name;
    document.getElementById('f_user').value = user;
    document.getElementById('f_pass').value = pass;
    document.getElementById('faculty-modal-title').innerText = 'Edit Faculty';
    document.getElementById('faculty-btn-label').innerText = 'Update Faculty';
    openModal('faculty-modal');
}

// Session
function openSessionModal() {
    document.getElementById('s_id').value = '0';
    document.getElementById('s_year').value = new Date().getFullYear();
    document.getElementById('s_term').value = '';
    document.getElementById('session-modal-title').innerText = 'Add Session';
    document.getElementById('session-btn-label').innerText = 'Save Session';
    openModal('session-modal');
}
function editSession(id, year, term) {
    document.getElementById('s_id').value = id;
    document.getElementById('s_year').value = year;
    document.getElementById('s_term').value = term;
    document.getElementById('session-modal-title').innerText = 'Edit Session';
    document.getElementById('session-btn-label').innerText = 'Update Session';
    openModal('session-modal');
}

// Course
function openCourseModal() {
    document.getElementById('c_id').value = '0';
    document.getElementById('c_code').value = '';
    document.getElementById('c_title').value = '';
    document.getElementById('c_credit').value = '3';
    document.getElementById('course-modal-title').innerText = 'Add Course';
    document.getElementById('course-btn-label').innerText = 'Save Course';
    openModal('course-modal');
}
function editCourse(id, code, title, credit) {
    document.getElementById('c_id').value = id;
    document.getElementById('c_code').value = code;
    document.getElementById('c_title').value = title;
    document.getElementById('c_credit').value = credit;
    document.getElementById('course-modal-title').innerText = 'Edit Course';
    document.getElementById('course-btn-label').innerText = 'Update Course';
    openModal('course-modal');
}
</script>

<?php require_once "footer.php"; ?>
