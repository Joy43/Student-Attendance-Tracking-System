<?php
require_once "header.php";

$courses  = mysqli_query($conn, "SELECT id, title, code FROM course_details ORDER BY title");
$sessions = mysqli_query($conn, "SELECT id, year, term FROM session_details ORDER BY year DESC");

$message = ""; $msg_type = "success";

// ─── Handle Submission ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $course_id  = (int) $_POST['course_id'];
    $session_id = (int) $_POST['session_id'];
    $on_date    = mysqli_real_escape_string($conn, $_POST['on_date']);

    $fq = mysqli_query($conn, "SELECT id FROM faculty_details WHERE user_name='$faculty_user'");
    $faculty_id = mysqli_fetch_assoc($fq)['id'] ?? 0;

    mysqli_begin_transaction($conn);
    $ok = $dup = 0;
    try {
        foreach ($_POST['attendance'] as $sid => $status) {
            $sid    = (int) $sid;
            $status = mysqli_real_escape_string($conn, $status);
            $sql = "INSERT INTO attendance_details (faculty_id,course_id,session_id,student_id,on_date,status)
                    VALUES ('$faculty_id','$course_id','$session_id','$sid','$on_date','$status')";
            if (!mysqli_query($conn, $sql)) {
                if (strpos(mysqli_error($conn), 'Duplicate') !== false) $dup++;
                else throw new Exception(mysqli_error($conn));
            } else $ok++;
        }
        mysqli_commit($conn);
        if ($dup > 0 && $ok === 0) { $message = "Attendance already recorded for this date, course, and session."; $msg_type = "warning"; }
        else { $message = "Attendance saved for $ok students successfully!" . ($dup > 0 ? " ($dup already existed)" : ""); $msg_type = "success"; }
    } catch (Exception $e) { mysqli_rollback($conn); $message = $e->getMessage(); $msg_type = "error"; }
}

$students = mysqli_query($conn, "SELECT id, roll_no, name FROM student_details ORDER BY roll_no ASC");
$all_students = [];
if ($students) while ($r = mysqli_fetch_assoc($students)) $all_students[] = $r;
?>

<div class="page-header">
  <div class="page-breadcrumb"><span>Home</span> <span>›</span> <span style="color:var(--text)">Attendance</span></div>
  <h1 class="page-title">Take Attendance</h1>
  <p style="color:var(--text-secondary);font-size:.875rem;margin-top:2px">Select a course, session, and date to record attendance.</p>
</div>

<?php if ($message): ?>
<div class="alert-<?= $msg_type ?>" style="
  display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.875rem;font-weight:500;
  background:<?= $msg_type==='success' ? 'var(--present-bg)' : ($msg_type==='error' ? 'var(--absent-bg)' : 'var(--late-bg)') ?>;
  color:<?= $msg_type==='success' ? 'var(--present)' : ($msg_type==='error' ? 'var(--absent)' : 'var(--late)') ?>;
  border:1px solid <?= $msg_type==='success' ? 'rgba(22,163,74,.25)' : ($msg_type==='error' ? 'rgba(220,38,38,.25)' : 'rgba(234,88,12,.25)') ?>">
  <?= $msg_type==='success' ? '✅' : ($msg_type==='error' ? '❌' : '⚠️') ?>
  <span><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="">
  <!-- Filter Card -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">📋 Session Details</span></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Course</label>
          <select name="course_id" class="form-control" required>
            <option value="">— Select Course —</option>
            <?php while ($c = mysqli_fetch_assoc($courses)): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['code'].' — '.$c['title']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Session / Term</label>
          <select name="session_id" class="form-control" required>
            <option value="">— Select Session —</option>
            <?php while ($s = mysqli_fetch_assoc($sessions)): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['year'].' — '.$s['term']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Date</label>
          <input type="date" name="on_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>
    </div>
  </div>

  <!-- Student Table -->
  <div class="table-wrapper">
    <div class="table-toolbar">
      <div class="table-search">
        <span style="color:var(--text-disabled);width:14px;height:14px;flex-shrink:0"><?= icon('search') ?></span>
        <input type="text" id="student-search" placeholder="Filter students…" oninput="filterStudents(this.value)">
      </div>
      <button type="button" class="btn btn-success btn-sm" onclick="markAll('Present')">✅ All Present</button>
      <button type="button" class="btn btn-danger  btn-sm" onclick="markAll('Absent')">❌ All Absent</button>
      <span style="font-size:.8rem;color:var(--text-secondary);margin-left:auto"><?= count($all_students) ?> students</span>
    </div>
    <table>
      <thead>
        <tr>
          <th style="width:42px">#</th>
          <th>Student</th>
          <th>Roll No</th>
          <th style="min-width:300px">Attendance Status</th>
        </tr>
      </thead>
      <tbody id="student-tbody">
        <?php if (empty($all_students)): ?>
        <tr><td colspan="4" class="table-empty">No students found. <a href="manage_students.php" style="color:var(--primary)">Add students →</a></td></tr>
        <?php else: ?>
        <?php foreach ($all_students as $i => $row): ?>
        <tr class="student-row" data-name="<?= strtolower($row['name']) ?>" data-roll="<?= strtolower($row['roll_no']) ?>">
          <td style="color:var(--text-secondary);font-size:.8rem"><?= $i+1 ?></td>
          <td>
            <div class="student-cell">
              <div class="student-avatar" style="background:<?= ['linear-gradient(135deg,#6366F1,#2563EB)','linear-gradient(135deg,#F59E0B,#EF4444)','linear-gradient(135deg,#10B981,#0284C7)','linear-gradient(135deg,#7C3AED,#EC4899)'][($i) % 4] ?>">
                <?= strtoupper(substr($row['name'],0,1)) ?>
              </div>
              <div>
                <div class="student-name"><?= htmlspecialchars($row['name']) ?></div>
              </div>
            </div>
          </td>
          <td style="font-size:.8rem;font-weight:600;color:var(--text-secondary)"><?= htmlspecialchars($row['roll_no']) ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap" class="status-chips" data-id="<?= $row['id'] ?>">
              <span class="chip present" onclick="selectStatus(this,'Present',<?= $row['id'] ?>)">✅ Present</span>
              <span class="chip absent"  onclick="selectStatus(this,'Absent', <?= $row['id'] ?>)">❌ Absent</span>
              <span class="chip late"    onclick="selectStatus(this,'Late',   <?= $row['id'] ?>)">⏰ Late</span>
              <span class="chip leave"   onclick="selectStatus(this,'Leave',  <?= $row['id'] ?>)">📝 Leave</span>
            </div>
            <input type="hidden" name="attendance[<?= $row['id'] ?>]" id="att-<?= $row['id'] ?>" value="">
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    <?php if (!empty($all_students)): ?>
    <div style="padding:14px 16px;display:flex;justify-content:flex-end;border-top:1px solid var(--border)">
      <button type="submit" class="btn btn-primary">
        <?= icon('check') ?> Save Attendance Records
      </button>
    </div>
    <?php endif; ?>
  </div>
</form>

<script>
function selectStatus(chip, status, id) {
  const row = chip.closest('.status-chips');
  row.querySelectorAll('.chip').forEach(c => c.classList.remove('selected'));
  chip.classList.add('selected');
  document.getElementById('att-' + id).value = status;
}

function markAll(status) {
  document.querySelectorAll('.status-chips').forEach(row => {
    const id = row.dataset.id;
    const chips = row.querySelectorAll('.chip');
    chips.forEach(c => c.classList.remove('selected'));
    const target = Array.from(chips).find(c => c.textContent.trim().toLowerCase().includes(status.toLowerCase()));
    if (target) { target.classList.add('selected'); document.getElementById('att-' + id).value = status; }
  });
  showToast('Bulk Action', 'All students marked as ' + status, status === 'Present' ? 'success' : 'warning');
}

function filterStudents(query) {
  const q = query.toLowerCase();
  document.querySelectorAll('.student-row').forEach(tr => {
    const match = tr.dataset.name.includes(q) || tr.dataset.roll.includes(q);
    tr.style.display = match ? '' : 'none';
  });
}
</script>

<?php require_once "footer.php"; ?>
