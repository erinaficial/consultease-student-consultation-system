<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/slot_helpers.php';   // also sets the timezone

require_role('student');

// One-time message (for example after a booking attempt)
$flash = $_SESSION['flash'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);

// Departments that have faculty, for the filter dropdown
$departments = $pdo->query(
    "SELECT DISTINCT department FROM users
     WHERE role = 'faculty' AND department IS NOT NULL AND department <> ''
     ORDER BY department"
)->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Find Faculty';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3">Find a faculty member</h1>
<p class="page-subtitle">Search for a faculty member and choose an open time.</p>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flashType) ?>" role="alert"><?= e($flash) ?></div>
<?php endif; ?>

<form id="slot-search" method="get" class="row g-2 mb-4" role="search">
  <div class="col-md-5">
    <label for="q" class="visually-hidden">Search faculty</label>
    <input type="search" class="form-control" id="q" name="q" placeholder="Search faculty..." autocomplete="off">
  </div>
  <div class="col-md-3">
    <label for="department" class="visually-hidden">Department</label>
    <select class="form-select" id="department" name="department">
      <option value="">All departments</option>
      <?php foreach ($departments as $dept): ?>
        <option value="<?= e($dept) ?>"><?= e($dept) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <label for="date" class="visually-hidden">Filter by date</label>
    <input type="date" class="form-control" id="date" name="date" min="<?= date('Y-m-d') ?>">
  </div>
  <div class="col-md-2">
    <button type="submit" class="btn btn-outline-primary w-100">Search</button>
  </div>
</form>

<!-- Select a time, continue to the topic, and book: this form posts to book.php -->
<form id="booking-form" method="post" action="book.php">
  <?= csrf_field() ?>
  <input type="hidden" id="slot-id" name="slot_id">
  <div id="faculty-list" class="d-grid gap-3"></div>
  <p id="slots-empty" class="text-muted" hidden></p>

  <div id="booking-panel" class="card mt-3" hidden>
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h2 class="h5 mb-1">Selected consultation time</h2>
          <p id="selected-slot-summary" class="text-muted mb-0"></p>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="change-slot">Change time</button>
      </div>
      <label for="topic" class="form-label">Consultation topic</label>
      <input type="text" class="form-control mb-3" id="topic" name="topic" maxlength="255"
             placeholder="What would you like to discuss?" required>
      <button type="submit" class="btn btn-primary">Book consultation</button>
      <div class="note-callout mt-3">Each slot can only be booked once.</div>
    </div>
  </div>
</form>
<noscript><div class="alert alert-warning mt-3">JavaScript is required to search and book slots.</div></noscript>
<?php require __DIR__ . '/../includes/footer.php'; ?>
