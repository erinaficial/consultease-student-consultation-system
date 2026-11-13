<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/slot_helpers.php';   // also sets the timezone

require_login();                       // visitors who are not logged in are sent to login.php
$user = current_user();

$stats = [];      // label => number shown on a card
$actions = [];    // [link, label, button style]
$intro = '';
$adminCompleted = 0;

if ($user['role'] === 'student') {
    $stmt = $pdo->prepare('SELECT status, COUNT(*) AS c FROM consultations WHERE student_id = ? GROUP BY status');
    $stmt->execute([$user['id']]);
    $by = array_column($stmt->fetchAll(), 'c', 'status');
    $stats = [
        'Pending'   => (int)($by['pending'] ?? 0),
        'Approved'  => (int)($by['approved'] ?? 0),
        'Completed' => (int)($by['completed'] ?? 0),
    ];
    $intro = 'Find an open time with your faculty and track your consultation requests.';
    $actions = [
        ['browse.php', 'Find a consultation slot', 'primary'],
        ['my_consultations.php', 'View my consultations', 'outline-primary'],
    ];
} elseif ($user['role'] === 'faculty') {
    $open = $pdo->prepare("SELECT COUNT(*) FROM availability_slots WHERE faculty_id = ? AND status = 'open' AND slot_date >= ?");
    $open->execute([$user['id'], date('Y-m-d')]);
    $pending = $pdo->prepare("SELECT COUNT(*) FROM consultations WHERE faculty_id = ? AND status = 'pending'");
    $pending->execute([$user['id']]);
    $done = $pdo->prepare("SELECT COUNT(*) FROM consultations WHERE faculty_id = ? AND status = 'completed'");
    $done->execute([$user['id']]);
    $stats = [
        'Open slots'        => (int)$open->fetchColumn(),
        'Pending requests'  => (int)$pending->fetchColumn(),
        'Completed'         => (int)$done->fetchColumn(),
    ];
    $intro = 'Publish your available times and respond to student requests.';
    $actions = [
        ['requests.php', 'Review requests', 'primary'],
        ['slots.php', 'Manage my availability', 'outline-primary'],
    ];
} else {
    $totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $by = array_column($pdo->query('SELECT status, COUNT(*) AS c FROM consultations GROUP BY status')->fetchAll(), 'c', 'status');
    $stats = [
        'Total users' => $totalUsers,
        'Pending'     => (int)($by['pending'] ?? 0),
        'Approved'    => (int)($by['approved'] ?? 0),
    ];
    $adminCompleted = (int)($by['completed'] ?? 0);
    $useCharts = true;                 // tells footer.php to load Chart.js
}

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($user['role'] === 'admin'): ?>
  <h1 class="h3">Overview</h1>
  <p class="page-subtitle">Consultation and account summary.</p>

  <div class="row g-3 mb-4">
    <?php foreach ($stats as $label => $value): ?>
      <div class="col-6 col-md-4">
        <div class="stat-card">
          <div class="stat-label"><?= e($label) ?></div>
          <div class="stat-value"><?= (int)$value ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-body">
          <h2 class="panel-title">Consultations by month and status</h2>
          <div class="chart-box"><canvas id="status-chart" aria-label="Consultations by month and status" role="img"></canvas></div>
          <p id="chart-message" class="text-muted small mb-0"></p>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-body admin-links">
          <h2 class="panel-title">Administration</h2>
          <a href="users.php">Manage user accounts</a>
          <a href="reports.php">View consultation reports</a>
          <div class="text-muted mt-2">Completed: <?= $adminCompleted ?></div>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <section class="welcome-banner mb-4">
    <h1>Welcome, <?= e($user['full_name']) ?></h1>
    <p><?= e($intro) ?></p>
  </section>

  <div class="row g-3 mb-4">
    <?php foreach ($stats as $label => $value): ?>
      <div class="col-6 col-md-4">
        <div class="stat-card">
          <div class="stat-value"><?= (int)$value ?></div>
          <div class="stat-label"><?= e($label) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($actions as [$href, $label, $style]): ?>
      <a class="btn btn-<?= e($style) ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
