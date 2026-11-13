<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/slot_helpers.php';   // also sets the timezone

require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'mark_read') {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$user['id']]);
        $_SESSION['flash'] = 'All notifications marked as read.';
    }
    header('Location: notifications.php');
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// READ: the latest 50 notifications for this user
$stmt = $pdo->prepare(
    'SELECT id, message, is_read, created_at FROM notifications
     WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 50'
);
$stmt->execute([$user['id']]);
$items = $stmt->fetchAll();

$pageTitle = 'Notifications';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3">Notifications</h1>
<p class="page-subtitle">Updates about your consultations.</p>

<?php if ($flash): ?>
  <div class="alert alert-success" role="alert"><?= e($flash) ?></div>
<?php endif; ?>

<?php if (!$items): ?>
  <div class="alert alert-secondary">No notifications yet.</div>
<?php else: ?>
  <form method="post" class="mb-3">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="mark_read">
    <button type="submit" class="btn btn-outline-primary btn-sm">Mark all as read</button>
  </form>
  <div class="card">
    <ul class="list-group list-group-flush">
      <?php foreach ($items as $n): ?>
        <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
          <div>
            <div class="<?= $n['is_read'] ? '' : 'fw-semibold' ?>"><?= e($n['message']) ?></div>
            <div class="small text-muted"><?= e(date('M j, Y g:i A', strtotime($n['created_at']))) ?></div>
          </div>
          <?php if (!$n['is_read']): ?><span class="badge text-bg-primary">New</span><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
