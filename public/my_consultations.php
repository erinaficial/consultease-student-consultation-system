<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/consult_helpers.php';

require_role('student');
$user = current_user();

// One-time message
$flash = $_SESSION['flash'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['action'] ?? '') === 'cancel') {
        $id = (int)($_POST['consultation_id'] ?? 0);
        try {
            $pdo->beginTransaction();

            // Only THIS student's pending/approved booking can be cancelled
            $stmt = $pdo->prepare(
                "SELECT id, slot_id, faculty_id, slot_label FROM consultations
                 WHERE id = ? AND student_id = ? AND status IN ('pending','approved') FOR UPDATE"
            );
            $stmt->execute([$id, $user['id']]);
            $c = $stmt->fetch();

            if (!$c) {
                $pdo->rollBack();
                $_SESSION['flash'] = 'That booking cannot be cancelled.';
                $_SESSION['flash_type'] = 'warning';
            } else {
                // Cancel it and release the slot so another student can book it
                $pdo->prepare("UPDATE consultations SET status = 'cancelled', slot_id = NULL WHERE id = ?")
                    ->execute([$c['id']]);
                if ($c['slot_id']) {
                    $pdo->prepare("UPDATE availability_slots SET status = 'open' WHERE id = ?")
                        ->execute([$c['slot_id']]);
                }
                if ($c['faculty_id']) {
                    notify($pdo, (int)$c['faculty_id'], $user['full_name'] . ' cancelled the consultation on ' . $c['slot_label'] . '.');
                }
                $pdo->commit();
                $_SESSION['flash'] = 'Booking cancelled.';
            }
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash'] = 'Could not cancel the booking. Please try again.';
            $_SESSION['flash_type'] = 'warning';
        }
        header('Location: my_consultations.php');
        exit;
    }
}

// READ: this student's bookings, newest first
$stmt = $pdo->prepare(
    'SELECT c.id, c.topic, c.status, c.faculty_remarks, c.slot_label,
            s.meeting_mode, s.meeting_details, u.full_name AS faculty_name
     FROM consultations c
     LEFT JOIN users u ON u.id = c.faculty_id
     LEFT JOIN availability_slots s ON s.id = c.slot_id
     WHERE c.student_id = ?
     ORDER BY c.created_at DESC'
);
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

$pageTitle = 'My Consultations';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">My consultations</h1>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flashType) ?>" role="alert"><?= e($flash) ?></div>
<?php endif; ?>

<?php if (!$rows): ?>
  <div class="alert alert-secondary">You have no bookings yet. <a href="browse.php">Browse open slots</a>.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr><th>Faculty</th><th>When</th><th>Topic</th><th>Mode / location</th><th>Status</th><th>Remarks</th><th class="text-end">Action</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
        $locationComplete = validate_meeting_details(
            (string)($r['meeting_mode'] ?? ''),
            (string)($r['meeting_details'] ?? '')
        ) === [];
        ?>
        <tr>
          <td><?= e($r['faculty_name'] ?? 'Unknown') ?></td>
          <td><?= e($r['slot_label'] ?? '-') ?></td>
          <td><?= e($r['topic']) ?></td>
          <td>
            <?php if (in_array($r['status'], ['approved', 'completed'], true) && $locationComplete): ?>
              <span class="badge text-bg-secondary"><?= e(ucfirst($r['meeting_mode'])) ?></span>
              <?php if ($r['meeting_mode'] === 'onsite'): ?>
                <div class="small"><?= e($r['meeting_details']) ?></div>
              <?php else: ?>
                <div class="small">
                  <a href="<?= e($r['meeting_details']) ?>" target="_blank" rel="noopener noreferrer">
                    <?= e($r['meeting_details']) ?>
                  </a>
                </div>
              <?php endif; ?>
            <?php elseif (in_array($r['status'], ['pending', 'approved'], true) && !$locationComplete): ?>
              <span class="text-muted small">Location needed</span>
            <?php elseif ($r['status'] === 'pending'): ?>
              <span class="badge text-bg-secondary"><?= e(ucfirst($r['meeting_mode'])) ?></span>
              <div class="small text-muted">Exact details will be available after approval.</div>
            <?php elseif ($r['status'] === 'completed'): ?>
              <span class="text-muted small">Location not recorded</span>
            <?php endif; ?>
          </td>
          <td><?= status_badge($r['status']) ?></td>
          <td><?= e($r['faculty_remarks'] ?? '') ?></td>
          <td class="text-end">
            <?php if (in_array($r['status'], ['pending', 'approved'], true)): ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Cancel this booking?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="consultation_id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
