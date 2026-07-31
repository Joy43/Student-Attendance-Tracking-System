<?php
require_once "header.php";

$message = ""; $msg_type = "success";

// ─── Notification Helper ───
function saveNotification($conn, $faculty_user, $type, $title, $message_text) {
    $fu  = mysqli_real_escape_string($conn, $faculty_user);
    $tp  = mysqli_real_escape_string($conn, $type);
    $ti  = mysqli_real_escape_string($conn, $title);
    $msg = mysqli_real_escape_string($conn, $message_text);
    mysqli_query($conn, "INSERT INTO notifications (faculty_user, type, title, message) VALUES ('$fu','$tp','$ti','$msg')");
}

// ─── CRUD Actions ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_student'])) {
    $roll_no = mysqli_real_escape_string($conn, trim($_POST['roll_no']));
    $name    = mysqli_real_escape_string($conn, trim($_POST['name']));
    $sid     = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;

    if ($sid > 0) {
        $sql = "UPDATE student_details SET roll_no='$roll_no', name='$name' WHERE id='$sid'";
        if (mysqli_query($conn, $sql)) {
            $message = "Student updated successfully!";
            $msg_type = "success";
            saveNotification($conn, $faculty_user,
                'update',
                '✏️ Student Updated',
                "Profile of $name (Roll: $roll_no) was updated."
            );
        } else {
            $message = "Error: ".mysqli_error($conn);
            $msg_type = "error";
        }
    } else {
        $sql = "INSERT INTO student_details (roll_no, name) VALUES ('$roll_no','$name')";
        if (mysqli_query($conn, $sql)) {
            $message = "Student registered successfully!";
            $msg_type = "success";
            saveNotification($conn, $faculty_user,
                'create',
                '➕ New Student Registered',
                "$name (Roll: $roll_no) has been added to the system."
            );
        } else {
            $message = "Error: ".mysqli_error($conn);
            $msg_type = "error";
        }
    }
}

if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    // Fetch name before deletion for notification
    $dq  = mysqli_query($conn, "SELECT name, roll_no FROM student_details WHERE id='$did'");
    $del_student = $dq ? mysqli_fetch_assoc($dq) : ['name'=>'Unknown','roll_no'=>''];
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM attendance_details WHERE student_id='$did'");
        mysqli_query($conn, "DELETE FROM student_details WHERE id='$did'");
        mysqli_commit($conn);
        $message = "Student and attendance records deleted.";
        $msg_type = "success";
        saveNotification($conn, $faculty_user,
            'delete',
            '🗑️ Student Deleted',
            "{$del_student['name']} (Roll: {$del_student['roll_no']}) was removed from the system."
        );
    } catch(Exception $e) {
        mysqli_rollback($conn);
        $message = $e->getMessage();
        $msg_type = "error";
    }
}

$edit_student = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $eq  = mysqli_query($conn, "SELECT * FROM student_details WHERE id='$eid'");
    if ($eq && mysqli_num_rows($eq)) $edit_student = mysqli_fetch_assoc($eq);
}

// ─── Load Students with attendance summary ───
$students_q = mysqli_query($conn, "
    SELECT s.*,
      (SELECT COUNT(*) FROM attendance_details a WHERE a.student_id=s.id) AS total_att,
      (SELECT COUNT(*) FROM attendance_details a WHERE a.student_id=s.id AND a.status='Present') AS present_att
    FROM student_details s ORDER BY s.roll_no ASC
");
$students = []; if ($students_q) while ($r = mysqli_fetch_assoc($students_q)) $students[] = $r;
$total_students = count($students);

$avatar_gradients = [
    'linear-gradient(135deg,#6366F1,#2563EB)',
    'linear-gradient(135deg,#F59E0B,#EF4444)',
    'linear-gradient(135deg,#10B981,#0284C7)',
    'linear-gradient(135deg,#7C3AED,#EC4899)',
    'linear-gradient(135deg,#14B8A6,#06B6D4)',
    'linear-gradient(135deg,#F97316,#FBBF24)',
];
?>

<div class="page-header">
  <div class="page-breadcrumb"><span>Home</span><span>›</span><span style="color:var(--text)">Students</span></div>
  <h1 class="page-title">Student Management</h1>
  <p style="color:var(--text-secondary);font-size:.875rem;margin-top:2px"><?= $total_students ?> students registered</p>
</div>

<?php if ($message): ?>
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.875rem;font-weight:500;
  background:<?= $msg_type==='success'?'var(--present-bg)':($msg_type==='error'?'var(--absent-bg)':'var(--late-bg)') ?>;
  color:<?= $msg_type==='success'?'var(--present)':($msg_type==='error'?'var(--absent)':'var(--late)') ?>;
  border:1px solid <?= $msg_type==='success'?'rgba(22,163,74,.25)':($msg_type==='error'?'rgba(220,38,38,.25)':'rgba(234,88,12,.25)') ?>">
  <?= $msg_type==='success'?'✅':($msg_type==='error'?'❌':'⚠️') ?> <span><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:flex-start;" id="manage-layout">

  <!-- ── Student Directory ── -->
  <div>
    <div class="table-wrapper">
      <div class="table-toolbar">
        <div class="table-search">
          <span style="color:var(--text-disabled);width:14px;height:14px;flex-shrink:0"><?= icon('search') ?></span>
          <input type="text" id="student-filter" placeholder="Search students…" oninput="filterTable(this.value)">
        </div>
        <button class="btn btn-primary btn-sm" onclick="openModal('add-modal')">
          <?= icon('user-plus') ?> Add Student
        </button>
        <span style="font-size:.8rem;color:var(--text-secondary);margin-left:auto"><?= $total_students ?> total</span>
      </div>
      <table>
        <thead>
          <tr>
            <th style="width:44px"></th>
            <th>Student</th>
            <th>Roll No</th>
            <th>Attendance</th>
            <th style="width:100px;text-align:center">Actions</th>
          </tr>
        </thead>
        <tbody id="student-tbody">
          <?php if (empty($students)): ?>
          <tr><td colspan="5" class="table-empty">
            <div class="empty-state"><div class="icon">👥</div><div class="title">No students yet</div><p>Add your first student using the button above.</p></div>
          </td></tr>
          <?php else: ?>
          <?php foreach ($students as $i => $s): ?>
          <?php
            $pct = $s['total_att'] > 0 ? round(($s['present_att']/$s['total_att'])*100) : 0;
            $pct_color = $pct >= 75 ? 'var(--present)' : ($pct >= 50 ? 'var(--late)' : 'var(--absent)');
            $grad = $avatar_gradients[$i % count($avatar_gradients)];
          ?>
          <tr class="student-row" data-name="<?= strtolower($s['name']) ?>" data-roll="<?= strtolower($s['roll_no']) ?>">
            <td>
              <div class="student-avatar" style="width:36px;height:36px;font-size:.8rem;background:<?= $grad ?>;cursor:pointer"
                   onclick="viewProfile(<?= htmlspecialchars(json_encode($s)) ?>, '<?= $grad ?>')">
                <?= strtoupper(substr($s['name'],0,1)) ?>
              </div>
            </td>
            <td>
              <div class="student-name"><?= htmlspecialchars($s['name']) ?></div>
              <div class="student-roll">ID: <?= $s['id'] ?></div>
            </td>
            <td style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($s['roll_no']) ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div style="flex-grow:1;height:4px;background:var(--border);border-radius:999px;overflow:hidden">
                  <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct_color ?>;border-radius:999px;transition:width .6s"></div>
                </div>
                <span style="font-size:.75rem;font-weight:700;color:<?= $pct_color ?>;min-width:36px"><?= $pct ?>%</span>
              </div>
              <div style="font-size:.7rem;color:var(--text-secondary);margin-top:2px"><?= $s['total_att'] ?> sessions</div>
            </td>
            <td>
              <div style="display:flex;justify-content:center;gap:6px">
                <button class="btn-icon-sm" title="View Profile" onclick="viewProfile(<?= htmlspecialchars(json_encode($s)) ?>,'<?= $grad ?>')"><?= icon('eye') ?></button>
                <button class="btn-icon-sm" title="Edit" onclick="editStudent(<?= $s['id'] ?>,'<?= htmlspecialchars(addslashes($s['roll_no'])) ?>','<?= htmlspecialchars(addslashes($s['name'])) ?>')"><?= icon('edit') ?></button>
                <a href="?delete=<?= $s['id'] ?>" class="btn-icon-sm" title="Delete" style="color:var(--absent)" onclick="return confirm('Delete <?= htmlspecialchars($s['name']) ?>? This removes all their attendance records.')"><?= icon('trash') ?></a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Add/Edit Form ── -->
  <div class="card" id="form-card">
    <div class="card-header">
      <span class="card-title" id="form-title"><?= $edit_student ? '✏️ Edit Student' : '➕ Register Student' ?></span>
    </div>
    <div class="card-body">
      <form method="POST" action="manage_students.php" id="student-form">
        <input type="hidden" name="student_id" id="form-sid" value="<?= $edit_student ? $edit_student['id'] : '' ?>">
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">Roll Number</label>
          <input type="text" name="roll_no" id="form-roll" class="form-control" value="<?= $edit_student ? htmlspecialchars($edit_student['roll_no']) : '' ?>" placeholder="e.g. CSB21011" required>
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" id="form-name" class="form-control" value="<?= $edit_student ? htmlspecialchars($edit_student['name']) : '' ?>" placeholder="e.g. John Doe" required>
        </div>
        <div style="display:flex;gap:10px">
          <button type="submit" name="save_student" class="btn btn-primary" style="flex-grow:1">
            <?= icon('check') ?> <span id="form-btn-label"><?= $edit_student ? 'Update Profile' : 'Register Student' ?></span>
          </button>
          <button type="button" class="btn btn-secondary" onclick="resetForm()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

</div>

<!-- ══════════ Profile Modal ══════════ -->
<div class="modal-overlay" id="profile-modal">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-header">
      <span class="modal-title">Student Profile</span>
      <button class="modal-close" onclick="closeModal('profile-modal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="profile-card" id="modal-profile-content">
        <!-- filled by JS -->
      </div>
      <div id="modal-info-rows"></div>
    </div>
    <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px">
      <button class="btn btn-secondary btn-sm" onclick="closeModal('profile-modal')">Close</button>
      <button class="btn btn-primary btn-sm" id="modal-edit-btn">✏️ Edit</button>
    </div>
  </div>
</div>

<!-- ══════════ Scripts ══════════ -->
<script>
function filterTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.student-row').forEach(tr => {
    tr.style.display = (tr.dataset.name.includes(q) || tr.dataset.roll.includes(q)) ? '' : 'none';
  });
}

function editStudent(id, roll, name) {
  document.getElementById('form-sid').value = id;
  document.getElementById('form-roll').value = roll;
  document.getElementById('form-name').value = name;
  document.getElementById('form-title').textContent = '✏️ Edit Student';
  document.getElementById('form-btn-label').textContent = 'Update Profile';
  document.getElementById('form-card').scrollIntoView({behavior:'smooth'});
  document.getElementById('form-roll').focus();
}

function resetForm() {
  document.getElementById('form-sid').value = '';
  document.getElementById('form-roll').value = '';
  document.getElementById('form-name').value = '';
  document.getElementById('form-title').textContent = '➕ Register Student';
  document.getElementById('form-btn-label').textContent = 'Register Student';
}

function viewProfile(s, grad) {
  const pct = s.total_att > 0 ? Math.round((s.present_att / s.total_att) * 100) : 0;
  const pctColor = pct >= 75 ? '#16A34A' : (pct >= 50 ? '#EA580C' : '#DC2626');
  document.getElementById('modal-profile-content').innerHTML = `
    <div class="profile-avatar" style="background:${grad}">${s.name.charAt(0).toUpperCase()}</div>
    <div class="profile-name">${s.name}</div>
    <div class="profile-role">Roll No: ${s.roll_no}</div>
    <div style="margin-top:12px;width:100%;text-align:center">
      <div style="font-size:.75rem;font-weight:700;color:${pctColor};margin-bottom:6px">Attendance: ${pct}%</div>
      <div style="height:8px;background:var(--border);border-radius:999px;overflow:hidden">
        <div style="height:100%;width:${pct}%;background:${pctColor};border-radius:999px;transition:width 1s"></div>
      </div>
      <div style="font-size:.75rem;color:var(--text-secondary);margin-top:6px">${s.present_att} present of ${s.total_att} sessions</div>
    </div>
  `;
  document.getElementById('modal-info-rows').innerHTML = `
    <div class="info-row"><span class="info-label">Student ID</span><span>#${s.id}</span></div>
    <div class="info-row"><span class="info-label">Roll Number</span><span>${s.roll_no}</span></div>
    <div class="info-row"><span class="info-label">Full Name</span><span>${s.name}</span></div>
    <div class="info-row"><span class="info-label">Total Sessions</span><span>${s.total_att}</span></div>
    <div class="info-row"><span class="info-label">Present</span><span style="color:#16A34A;font-weight:700">${s.present_att}</span></div>
    <div class="info-row"><span class="info-label">Absent/Late/Leave</span><span style="color:#DC2626;font-weight:700">${parseInt(s.total_att) - parseInt(s.present_att)}</span></div>
  `;
  document.getElementById('modal-edit-btn').onclick = () => { closeModal('profile-modal'); editStudent(s.id, s.roll_no, s.name); };
  openModal('profile-modal');
}
</script>

<?php require_once "footer.php"; ?>
