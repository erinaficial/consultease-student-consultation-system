<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/consult_helpers.php';

require_role('faculty');
$user = current_user();

// One-time message
$flash = $_SESSION['flash'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id      = (int)($_POST['consultation_id'] ?? 0);
    $action  = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $remarks = mb_substr(trim($_POST['remarks'] ?? ''), 0, 1000);

    try {
        $pdo->beginTransaction();

        // Load the consultation, but ONLY if it belongs to this faculty member
        $stmt = $pdo->prepare(
            'SELECT id, slot_id, student_id, slot_label, status FROM consultations
             WHERE id = ? AND faculty_id = ? FOR UPDATE'
        );
        $stmt->execute([$id, $user['id']]);
        $c = $stmt->fetch();

        $slot = null;
        if ($c && $c['slot_id'] !== null) {
            $slotStmt = $pdo->prepare(
                'SELECT id, status, meeting_mode, meeting_details
                 FROM availability_slots
                 WHERE id = ? AND faculty_id = ? FOR UPDATE'
            );
            $slotStmt->execute([$c['slot_id'], $user['id']]);
            $slot = $slotStmt->fetch();
        }
        $locationComplete = $slot
            && validate_meeting_details(
                (string)$slot['meeting_mode'],
                (string)($slot['meeting_details'] ?? '')
            ) === [];

        $message = '';
        $warning = '';
        if ($c && $action === 'approve' && $c['status'] === 'pending') {
            if (!$locationComplete) {
                $warning = 'Add the meeting mode and location before approving this request.';
            } else {
                $pdo->prepare("UPDATE consultations SET status = 'approved', faculty_remarks = ? WHERE id = ?")
                    ->execute([$remarks, $c['id']]);
                notify($pdo, (int)$c['student_id'], 'Your consultation on ' . $c['slot_label'] . ' was approved.');
                $message = 'Consultation approved.';
            }

        } elseif ($c && $action === 'save_location' && in_array($c['status'], ['pending', 'approved'], true)) {
            if (!$slot || $slot['status'] !== 'booked') {
                $warning = 'That consultation location cannot be updated.';
            } elseif ($locationComplete) {
                $warning = 'The meeting mode and location are already set.';
            } else {
                $meetingMode = is_string($_POST['meeting_mode'] ?? null) ? trim($_POST['meeting_mode']) : '';
                $meetingDetails = is_string($_POST['meeting_details'] ?? null) ? trim($_POST['meeting_details']) : '';
                $validationErrors = validate_meeting_details($meetingMode, $meetingDetails);

                if ($validationErrors) {
                    $warning = implode(' ', $validationErrors);
                } else {
                    $upd = $pdo->prepare(
                        "UPDATE availability_slots
                         SET meeting_mode = ?, meeting_details = ?
                         WHERE id = ? AND faculty_id = ? AND status = 'booked'"
                    );
                    $upd->execute([$meetingMode, $meetingDetails, $c['slot_id'], $user['id']]);

                    if ($upd->rowCount() !== 1) {
                        $warning = 'Could not save the consultation location. Please try again.';
                    } else {
                        if ($c['status'] === 'approved' && !$locationComplete) {
                            notify($pdo, (int)$c['student_id'], 'Your consultation location is now available.');
                        }
                        $message = 'Consultation location saved.';
                    }
                }
            }

        } elseif ($c && $action === 'decline' && $c['status'] === 'pending') {
            // Declining releases the slot so another student can book it
            $pdo->prepare("UPDATE consultations SET status = 'declined', faculty_remarks = ?, slot_id = NULL WHERE id = ?")
                ->execute([$remarks, $c['id']]);
            if ($c['slot_id']) {
                $pdo->prepare("UPDATE availability_slots SET status = 'open' WHERE id = ?")->execute([$c['slot_id']]);
            }
            notify($pdo, (int)$c['student_id'], 'Your consultation on ' . $c['slot_label'] . ' was declined.');
            $message = 'Consultation declined.';

        } elseif ($c && $action === 'complete' && $c['status'] === 'approved') {
            if (!$locationComplete) {
                $warning = 'Add the meeting mode and location before marking this request completed.';
            } else {
                $pdo->prepare("UPDATE consultations SET status = 'completed', faculty_remarks = ? WHERE id = ?")
                    ->execute([$remarks, $c['id']]);
                notify($pdo, (int)$c['student_id'], 'Your consultation on ' . $c['slot_label'] . ' was marked as completed.');
                $message = 'Consultation marked as completed.';
            }
        }

        if ($message !== '') {
            $pdo->commit();
            $_SESSION['flash'] = $message;
        } else {
            $pdo->rollBack();
            $_SESSION['flash'] = $warning ?: 'That action is not allowed for this consultation.';
            $_SESSION['flash_type'] = 'warning';
        }
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['flash'] = 'Something went wrong. Please try again.';
        $_SESSION['flash_type'] = 'warning';
    }
    header('Location: requests.php');
    exit;
}

// READ: this faculty member's consultations, newest first, split by status
$stmt = $pdo->prepare(
    "SELECT c.id, c.slot_id, c.topic, c.status, c.faculty_remarks, c.slot_label,
            s.meeting_mode, s.meeting_details, u.full_name AS student_name, u.department
     FROM consultations c
     JOIN users u ON u.id = c.student_id
     LEFT JOIN availability_slots s ON s.id = c.slot_id
     WHERE c.faculty_id = ?
     ORDER BY c.created_at DESC"
);
$stmt->execute([$user['id']]);
$all = $stmt->fetchAll();
foreach ($all as &$row) {
    $row['location_complete'] = validate_meeting_details(
        (string)($row['meeting_mode'] ?? ''),
        (string)($row['meeting_details'] ?? '')
    ) === [];
}
unset($row);

$pending  = array_values(array_filter($all, fn($r) => $r['status'] === 'pending'));
$approved = array_values(array_filter($all, fn($r) => $r['status'] === 'approved'));
$history  = array_values(array_filter($all, fn($r) => !in_array($r['status'], ['pending', 'approved'], true)));

$openStmt = $pdo->prepare("SELECT COUNT(*) FROM availability_slots WHERE faculty_id = ? AND status = 'open' AND slot_date >= ?");
$openStmt->execute([$user['id'], date('Y-m-d')]);
$openSlots = (int)$openStmt->fetchColumn();

// One row of the "pending" or "approved" table, with location and action forms
function request_row(array $r, string $mode): void { ?>
  <tr>
    <td>
      <div class="fw-semibold"><?= e($r['student_name']) ?></div>
      <div class="small text-muted"><?= e($r['department'] ?? '') ?></div>
    </td>
    <td><?= e($r['slot_label'] ?? '-') ?></td>
    <td><?= e($r['topic']) ?></td>
    <td>
      <?php if ($r['location_complete']): ?>
        <span class="badge text-bg-secondary"><?= e(ucfirst($r['meeting_mode'])) ?></span>
        <div class="small"><?= e($r['meeting_details']) ?></div>
      <?php else: ?>
        <div class="text-muted small mb-2">Location needed</div>
        <?php if ($r['slot_id'] !== null): ?>
          <form method="post" class="d-grid gap-2 mb-2" data-meeting-details-form>
            <?= csrf_field() ?>
            <input type="hidden" name="consultation_id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="action" value="save_location">
            <label for="meeting_mode_<?= (int)$r['id'] ?>" class="visually-hidden">Meeting mode</label>
            <select class="form-select form-select-sm" id="meeting_mode_<?= (int)$r['id'] ?>" name="meeting_mode" required>
              <option value="">Choose a mode</option>
              <option value="onsite" <?= ($r['meeting_mode'] ?? '') === 'onsite' ? 'selected' : '' ?>>Onsite</option>
              <option value="online" <?= ($r['meeting_mode'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
            </select>
            <label for="meeting_details_<?= (int)$r['id'] ?>" class="visually-hidden" data-meeting-details-label>Room or location</label>
            <input type="text" class="form-control form-control-sm" id="meeting_details_<?= (int)$r['id'] ?>"
                   name="meeting_details" maxlength="2048" value="<?= e($r['meeting_details'] ?? '') ?>" required>
            <button type="submit" class="btn btn-outline-primary btn-sm">Save location</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </td>
    <td>
      <form method="post" class="d-flex flex-wrap gap-2 align-items-center">
        <?= csrf_field() ?>
        <input type="hidden" name="consultation_id" value="<?= (int)$r['id'] ?>">
        <input type="text" class="form-control form-control-sm remarks-input" name="remarks" maxlength="1000"
               placeholder="Add remarks..." value="<?= e($r['faculty_remarks'] ?? '') ?>"
               aria-label="Remarks for <?= e($r['student_name']) ?>">
        <?php if ($mode === 'pending'): ?>
          <?php if ($r['location_complete']): ?>
            <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">Approve</button>
          <?php else: ?>
            <span class="text-muted small">Complete location before approval.</span>
          <?php endif; ?>
          <button type="submit" name="action" value="decline" class="btn btn-outline-danger btn-sm">Decline</button>
        <?php else: ?>
          <?php if ($r['location_complete']): ?>
            <button type="submit" name="action" value="complete" class="btn btn-primary btn-sm">Mark as completed</button>
          <?php else: ?>
            <span class="text-muted small">Complete location before marking this request done.</span>
          <?php endif; ?>
        <?php endif; ?>
      </form>
    </td>
  </tr>
<?php }

$pageTitle = 'Requests';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3">Consultation requests</h1>
<p class="page-subtitle">Review requests and update their status.</p>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flashType) ?>" role="alert"><?= e($flash) ?></div>
<?php endif; ?>

<div class="row g-3 mb-2">
  <div class="col-6 col-md-4">
    <div class="stat-card">
      <div class="stat-label">Pending requests</div>
      <div class="stat-value"><?= count($pending) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="stat-card">
      <div class="stat-label">Open slots</div>
      <div class="stat-value"><?= $openSlots ?></div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="stat-card">
      <div class="stat-label">Availability</div>
      <a class="stat-value" href="slots.php" style="text-decoration:none;">Manage</a>
    </div>
  </div>
</div>

<div class="section-label">Pending</div>
<?php if (!$pending): ?>
  <div class="alert alert-secondary">No pending requests.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Student</th><th>Date / time</th><th>Topic</th><th>Mode / location</th><th>Remarks and action</th></tr></thead>
      <tbody><?php foreach ($pending as $r) { request_row($r, 'pending'); } ?></tbody>
    </table>
  </div>
<?php endif; ?>

<?php if ($approved): ?>
  <div class="section-label">Approved</div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Student</th><th>Date / time</th><th>Topic</th><th>Mode / location</th><th>Remarks and action</th></tr></thead>
      <tbody><?php foreach ($approved as $r) { request_row($r, 'approved'); } ?></tbody>
    </table>
  </div>
<?php endif; ?>

<?php if ($history): ?>
  <div class="section-label">History</div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Student</th><th>Date / time</th><th>Topic</th><th>Status</th><th>Remarks</th></tr></thead>
      <tbody>
      <?php foreach ($history as $r): ?>
        <tr>
          <td><?= e($r['student_name']) ?></td>
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
