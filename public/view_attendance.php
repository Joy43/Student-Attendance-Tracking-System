<?php
require_once "../src/header.php";

// ─── Filters ───
$filter_course  = isset($_GET['course_id'])  ? (int)$_GET['course_id']  : 0;
$filter_session = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$filter_date    = isset($_GET['on_date'])    ? mysqli_real_escape_string($conn, $_GET['on_date'])  : '';
$filter_student = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$filter_status  = isset($_GET['status'])     ? mysqli_real_escape_string($conn, $_GET['status'])  : '';

$courses  = mysqli_query($conn, "SELECT id, code, title FROM course_details ORDER BY code");
$sessions = mysqli_query($conn, "SELECT id, year, term FROM session_details ORDER BY year DESC");
$students_dd = mysqli_query($conn, "SELECT id, roll_no, name FROM student_details ORDER BY roll_no");

// ─── Query Builder ───
$where = ["1=1"];
if ($filter_course)  $where[] = "a.course_id='$filter_course'";
if ($filter_session) $where[] = "a.session_id='$filter_session'";
if ($filter_date)    $where[] = "a.on_date='$filter_date'";
if ($filter_student) $where[] = "a.student_id='$filter_student'";
if ($filter_status)  $where[] = "a.status='$filter_status'";
$where_str = implode(' AND ', $where);

$records_q = mysqli_query($conn, "
    SELECT a.on_date, a.status,
           s.name, s.roll_no,
           c.code AS course_code, c.title AS course_title,
           se.year, se.term,
           f.name AS faculty_name
    FROM attendance_details a
    JOIN student_details s  ON a.student_id  = s.id
    JOIN course_details c   ON a.course_id   = c.id
    JOIN session_details se ON a.session_id  = se.id
    LEFT JOIN faculty_details f ON a.faculty_id = f.id
    WHERE $where_str
    ORDER BY a.on_date DESC, s.roll_no ASC
");
$records = []; if ($records_q) while ($r = mysqli_fetch_assoc($records_q)) $records[] = $r;

// ─── Summary Counts ───
$count = count($records);
$counts = ['Present'=>0,'Absent'=>0,'Late'=>0,'Leave'=>0];
foreach ($records as $r) { if (isset($counts[$r['status']])) $counts[$r['status']]++; }
?>

<div class="page-header">
  <div class="page-breadcrumb"><span>Home</span><span>›</span><span style="color:var(--text)">Reports</span></div>
  <h1 class="page-title">Attendance Reports</h1>
  <p style="color:var(--text-secondary);font-size:.875rem;margin-top:2px">Filter, browse, and export attendance records.</p>
</div>

<!-- ─── Mini Stats ─── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px">
  <?php foreach (['Present'=>['#16A34A','✅'],'Absent'=>['#DC2626','❌'],'Late'=>['#EA580C','⏰'],'Leave'=>['#7C3AED','📝']] as $s=>[$col,$em]): ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;box-shadow:var(--shadow-sm);display:flex;align-items:center;gap:12px;border-left:3px solid <?= $col ?>">
    <span style="font-size:1.2rem"><?= $em ?></span>
    <div>
      <div style="font-size:1.4rem;font-weight:800;letter-spacing:-.04em;color:<?= $col ?>"><?= $counts[$s] ?></div>
      <div style="font-size:.7rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em"><?= $s ?></div>
    </div>
  </div>
  <?php endforeach; ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;box-shadow:var(--shadow-sm);display:flex;align-items:center;gap:12px;border-left:3px solid var(--primary)">
    <span style="font-size:1.2rem">📋</span>
    <div>
      <div style="font-size:1.4rem;font-weight:800;letter-spacing:-.04em;color:var(--primary)"><?= $count ?></div>
      <div style="font-size:.7rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em">Total</div>
    </div>
  </div>
</div>

<!-- ─── Filters ─── -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <span class="card-title">🔎 Filter Records</span>
    <a href="view_attendance.php" class="btn btn-ghost btn-sm">Clear Filters</a>
  </div>
  <div class="card-body">
    <form method="GET" action="view_attendance.php">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Course</label>
          <select name="course_id" class="form-control">
            <option value="">All Courses</option>
            <?php while ($c = mysqli_fetch_assoc($courses)): ?>
            <option value="<?= $c['id'] ?>" <?= $filter_course == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['code'].' — '.$c['title']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Session</label>
          <select name="session_id" class="form-control">
            <option value="">All Sessions</option>
            <?php while ($s = mysqli_fetch_assoc($sessions)): ?>
            <option value="<?= $s['id'] ?>" <?= $filter_session == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['year'].' — '.$s['term']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Student</label>
          <select name="student_id" class="form-control">
            <option value="">All Students</option>
            <?php while ($s = mysqli_fetch_assoc($students_dd)): ?>
            <option value="<?= $s['id'] ?>" <?= $filter_student == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['roll_no'].' — '.$s['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Date</label>
          <input type="date" name="on_date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <option value="Present" <?= $filter_status==='Present'?'selected':'' ?>>✅ Present</option>
            <option value="Absent"  <?= $filter_status==='Absent' ?'selected':'' ?>>❌ Absent</option>
            <option value="Late"    <?= $filter_status==='Late'   ?'selected':'' ?>>⏰ Late</option>
            <option value="Leave"   <?= $filter_status==='Leave'  ?'selected':'' ?>>📝 Leave</option>
          </select>
        </div>
        <div class="form-group" style="justify-content:flex-end;align-self:flex-end">
          <button type="submit" class="btn btn-grad btn-full">Apply Filters</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- ─── Results Table ─── -->
<div class="table-wrapper">
  <div class="table-toolbar">
    <div class="table-search">
      <span style="color:var(--text-disabled);width:14px;height:14px;flex-shrink:0"><?= icon('search') ?></span>
      <input type="text" id="report-search" placeholder="Search name, roll, course…" oninput="filterReport(this.value)">
    </div>
    <button class="btn btn-secondary btn-sm" onclick="exportCSV()">
      Export CSV
    </button>
    <span style="font-size:.8rem;color:var(--text-secondary);margin-left:auto"><?= $count ?> records</span>
  </div>
  <table id="report-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Student</th>
        <th>Roll No</th>
        <th>Course</th>
        <th>Session</th>
        <th>Date</th>
        <th>Status</th>
        <th>Faculty</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($records)): ?>
      <tr><td colspan="8" class="table-empty">
        <div class="empty-state">
          <div class="icon">📊</div>
          <div class="title">No records found</div>
          <p>Try adjusting your filters or <a href="take_attendance.php" style="color:var(--primary)">record attendance</a>.</p>
        </div>
      </td></tr>
      <?php else: ?>
      <?php foreach ($records as $i => $r): ?>
      <?php
        $status_chip = ['Present'=>'present','Absent'=>'absent','Late'=>'late','Leave'=>'leave'];
        $chip_class  = $status_chip[$r['status']] ?? '';
      ?>
      <tr class="report-row" data-search="<?= strtolower($r['name'].' '.$r['roll_no'].' '.$r['course_code'].' '.$r['status']) ?>">
        <td style="color:var(--text-disabled);font-size:.8rem"><?= $i+1 ?></td>
        <td>
          <div class="student-cell">
            <div class="student-avatar" style="width:30px;height:30px;font-size:.72rem">
              <?= strtoupper(substr($r['name'],0,1)) ?>
            </div>
            <span class="student-name" style="font-size:.85rem"><?= htmlspecialchars($r['name']) ?></span>
          </div>
        </td>
        <td style="font-size:.8rem;font-weight:600"><?= htmlspecialchars($r['roll_no']) ?></td>
        <td>
          <div style="font-weight:600;font-size:.8rem"><?= htmlspecialchars($r['course_code']) ?></div>
          <div style="font-size:.72rem;color:var(--text-secondary)"><?= htmlspecialchars($r['course_title']) ?></div>
        </td>
        <td style="font-size:.8rem"><?= htmlspecialchars($r['year'].' – '.$r['term']) ?></td>
        <td style="font-size:.8rem;white-space:nowrap"><?= date('M j, Y', strtotime($r['on_date'])) ?></td>
        <td><span class="chip <?= $chip_class ?>" style="cursor:default"><?= htmlspecialchars($r['status']) ?></span></td>
        <td style="font-size:.8rem;color:var(--text-secondary)"><?= htmlspecialchars($r['faculty_name'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function filterReport(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.report-row').forEach(tr => {
    tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
  });
}

function exportCSV() {
  const rows = [['#','Student','Roll No','Course','Session','Date','Status','Faculty']];
  document.querySelectorAll('#report-table tbody .report-row').forEach((tr, i) => {
    const cells = tr.querySelectorAll('td');
    rows.push([
      i+1,
      cells[1]?.innerText?.trim(),
      cells[2]?.innerText?.trim(),
      cells[3]?.innerText?.trim(),
      cells[4]?.innerText?.trim(),
      cells[5]?.innerText?.trim(),
      cells[6]?.innerText?.trim(),
      cells[7]?.innerText?.trim(),
    ]);
  });
  const csv = rows.map(r => r.map(c => `"${String(c||'').replace(/"/g,'""')}"`).join(',')).join('\n');
  const a = document.createElement('a');
  a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
  a.download = 'attendance_report_<?= date('Y-m-d') ?>.csv';
  a.click();
  showToast('Export Complete', 'CSV report downloaded successfully.', 'success');
}
</script>

<?php require_once "../src/footer.php"; ?>
