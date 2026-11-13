<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/consult_helpers.php';

require_role('admin');

$statuses = ['pending', 'approved', 'declined', 'completed', 'cancelled'];

// Filters (all optional)
$status  = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$month   = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : '';
$faculty = (int)($_GET['faculty'] ?? 0);

$where = [];
$params = [];
if ($status !== '')  { $where[] = 'c.status = ?';                           $params[] = $status; }
if ($month !== '')   { $where[] = "DATE_FORMAT(c.created_at, '%Y-%m') = ?"; $params[] = $month; }
if ($faculty > 0)    { $where[] = 'c.faculty_id = ?';                       $params[] = $faculty; }

$sql = "SELECT c.id, c.topic, c.status, c.slot_label, c.faculty_remarks, c.created_at,
               s.full_name AS student_name, f.full_name AS faculty_name
        FROM consultations c
        JOIN users s ON s.id = c.student_id
        LEFT JOIN users f ON f.id = c.faculty_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY c.created_at DESC LIMIT 1000';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Download the filtered rows as a CSV file (opens in Excel)
if (isset($_GET['export'])) {
    // Stops spreadsheet formulas: text starting with = + - @ gets a leading apostrophe
    $safe = static fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : (string)$v;

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="consultations_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");                           // UTF-8 marker so Excel reads it correctly
    fputcsv($out, ['ID', 'Student', 'Faculty', 'Date and time', 'Topic', 'Status', 'Remarks', 'Created']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $safe($r['student_name']), $safe($r['faculty_name'] ?? ''), $safe($r['slot_label'] ?? ''),
            $safe($r['topic']), $r['status'], $safe($r['faculty_remarks'] ?? ''), $r['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

// Summary counts from the filtered rows
$counts = array_fill_keys($statuses, 0);
foreach ($rows as $r) {
    $counts[$r['status']]++;
}

$facultyList = $pdo->query("SELECT id, full_name FROM users WHERE role = 'faculty' ORDER BY full_name")->fetchAll();
$exportQuery = http_build_query(array_filter(['status' => $status, 'month' => $month, 'faculty' => $faculty ?: null]) + ['export' => 1]);

$pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
  <div>
    <h1 class="h3">Consultation reports</h1>
    <p class="page-subtitle">Filter consultations and download them as a CSV file.</p>
  </div>
  <a class="btn btn-outline-primary" href="reports.php?<?= e($exportQuery) ?>">Download CSV</a>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-3">
    <label for="status" class="form-label small">Status</label>
    <select class="form-select" id="status" name="status">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label for="month" class="form-label small">Month booked</label>
    <input type="month" class="form-control" id="month" name="month" value="<?= e($month) ?>">
  </div>
  <div class="col-md-4">
    <label for="faculty" class="form-label small">Faculty</label>
    <select class="form-select" id="faculty" name="faculty">
      <option value="0">All faculty</option>
      <?php foreach ($facultyList as $f): ?>
        <option value="<?= (int)$f['id'] ?>" <?= $faculty === (int)$f['id'] ? 'selected' : '' ?>><?= e($f['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <button type="submit" class="btn btn-primary w-100">Apply</button>
  </div>
</form>

<div class="row g-3 mb-3">
  <?php foreach ($counts as $label => $n): ?>
    <div class="col-6 col-md">
      <div class="stat-card">
        <div class="stat-label"><?= e(ucfirst($label)) ?></div>
        <div class="stat-value"><?= (int)$n ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <div class="alert alert-secondary">No consultations match your filters.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr><th>Student</th><th>Faculty</th><th>When</th><th>Topic</th><th>Status</th><th>Remarks</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['student_name']) ?></td>
          <td><?= e($r['faculty_name'] ?? 'Unknown') ?></td>
          <td><?= e($r['slot_label'] ?? '-') ?></td>
          <td><?= e($r['topic']) ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td><?= e($r['faculty_remarks'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
