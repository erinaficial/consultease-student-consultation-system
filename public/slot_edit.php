<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/slot_helpers.php';

require_role('faculty');
$user = current_user();

// Load the slot, but ONLY if it belongs to this faculty member and is still open.
$id = (int)($_GET['id'] ?? $_POST['slot_id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT id, slot_date, start_time, end_time, meeting_mode, meeting_details FROM availability_slots
     WHERE id = ? AND faculty_id = ? AND status = 'open'"
);
$stmt->execute([$id, $user['id']]);
$slot = $stmt->fetch();

if (!$slot) {
    $_SESSION['flash'] = 'That slot cannot be edited. Only your own open slots can be changed.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: slots.php');
    exit;
}

$errors = [];
$date  = $slot['slot_date'];
$start = substr($slot['start_time'], 0, 5);   // "14:00:00" -> "14:00"
$end   = substr($slot['end_time'], 0, 5);
$meetingMode = $slot['meeting_mode'] ?? '';
$meetingDetails = $slot['meeting_details'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $date  = trim($_POST['slot_date'] ?? '');
    $start = trim($_POST['start_time'] ?? '');
    $end   = trim($_POST['end_time'] ?? '');
    $meetingMode = is_string($_POST['meeting_mode'] ?? null) ? trim($_POST['meeting_mode']) : '';
    $meetingDetails = is_string($_POST['meeting_details'] ?? null) ? trim($_POST['meeting_details']) : '';
    $errors = array_merge(
        validate_slot($pdo, $user['id'], $date, $start, $end, $id),   // $id = ignore this slot itself
        validate_meeting_details($meetingMode, $meetingDetails)
    );

    if (!$errors) {
        // UPDATE
        $upd = $pdo->prepare(
            "UPDATE availability_slots
             SET slot_date = ?, start_time = ?, end_time = ?, meeting_mode = ?, meeting_details = ?
             WHERE id = ? AND faculty_id = ? AND status = 'open'"
        );
        $upd->execute([$date, $start, $end, $meetingMode, $meetingDetails, $id, $user['id']]);
        $_SESSION['flash'] = 'Slot updated.';
        header('Location: slots.php');
        exit;
    }
}

$pageTitle = 'Edit Slot';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-8 col-lg-5">
    <h1 class="h3 mb-1">Edit slot</h1>
    <p class="text-muted small mb-3">School hours: <?= e(school_hours_text()) ?>, Monday to Saturday.</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" data-meeting-details-form>
      <?= csrf_field() ?>
      <input type="hidden" name="slot_id" value="<?= (int)$id ?>">
      <div class="mb-3">
        <label for="slot_date" class="form-label">Date</label>
        <input type="date" class="form-control" id="slot_date" name="slot_date"
               min="<?= date('Y-m-d') ?>" value="<?= e($date) ?>" required>
      </div>
      <div class="mb-3">
        <label for="start_time" class="form-label">Start time</label>
        <input type="time" class="form-control" id="start_time" name="start_time"
               min="<?= SCHOOL_OPEN ?>" max="<?= SCHOOL_CLOSE ?>" value="<?= e($start) ?>" required>
      </div>
      <div class="mb-3">
        <label for="end_time" class="form-label">End time</label>
        <input type="time" class="form-control" id="end_time" name="end_time"
               min="<?= SCHOOL_OPEN ?>" max="<?= SCHOOL_CLOSE ?>" value="<?= e($end) ?>" required>
      </div>
      <div class="mb-3">
        <label for="meeting_mode" class="form-label">Meeting mode</label>
        <select class="form-select" id="meeting_mode" name="meeting_mode" required>
          <option value="">Choose a mode</option>
          <option value="onsite" <?= $meetingMode === 'onsite' ? 'selected' : '' ?>>Onsite</option>
          <option value="online" <?= $meetingMode === 'online' ? 'selected' : '' ?>>Online</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="meeting_details" class="form-label" data-meeting-details-label>Room or location</label>
        <input type="text" class="form-control" id="meeting_details" name="meeting_details"
               maxlength="2048" value="<?= e($meetingDetails) ?>" required>
      </div>
      <button type="submit" class="btn btn-primary">Save changes</button>
      <a class="btn btn-outline-secondary" href="slots.php">Cancel</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
