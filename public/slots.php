<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/slot_helpers.php';

require_role('faculty');               // only faculty may open this page
$user = current_user();

$errors = [];
$date = $start = $end = $meetingMode = $meetingDetails = '';

// One-time message shown after an add / edit / delete
$flash = $_SESSION['flash'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // CREATE
        $date  = trim($_POST['slot_date'] ?? '');
        $start = trim($_POST['start_time'] ?? '');
        $end   = trim($_POST['end_time'] ?? '');
        $meetingMode = is_string($_POST['meeting_mode'] ?? null) ? trim($_POST['meeting_mode']) : '';
        $meetingDetails = is_string($_POST['meeting_details'] ?? null) ? trim($_POST['meeting_details']) : '';
        $errors = array_merge(
            validate_slot($pdo, $user['id'], $date, $start, $end),
            validate_meeting_details($meetingMode, $meetingDetails)
        );

        if (!$errors) {
            $stmt = $pdo->prepare(
                'INSERT INTO availability_slots
                 (faculty_id, slot_date, start_time, end_time, meeting_mode, meeting_details)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$user['id'], $date, $start, $end, $meetingMode, $meetingDetails]);
            $_SESSION['flash'] = 'Slot added.';
            header('Location: slots.php');   // redirect so refreshing does not re-submit the form
            exit;
        }
    } elseif ($action === 'delete') {
        // DELETE: only YOUR slot, and only while it is still open
        $id = (int)($_POST['slot_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM availability_slots WHERE id = ? AND faculty_id = ? AND status = 'open'");
        $stmt->execute([$id, $user['id']]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash'] = 'Slot deleted.';
        } else {
            $_SESSION['flash'] = 'Only your own open slots can be deleted.';
            $_SESSION['flash_type'] = 'warning';
        }
        header('Location: slots.php');
        exit;
    }
}

// READ: this faculty member's slots
$stmt = $pdo->prepare(
    'SELECT id, slot_date, start_time, end_time, meeting_mode, meeting_details, status
     FROM availability_slots WHERE faculty_id = ? ORDER BY slot_date, start_time'
);
$stmt->execute([$user['id']]);
$slots = $stmt->fetchAll();

$badges = ['open' => 'success', 'booked' => 'primary', 'cancelled' => 'secondary'];

$pageTitle = 'My Slots';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">My availability slots</h1>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flashType) ?>" role="alert"><?= e($flash) ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h2 class="h5 mb-1">Add a slot</h2>
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
          <input type="hidden" name="action" value="add">
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
          <button type="submit" class="btn btn-primary w-100">Add slot</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <?php if (!$slots): ?>
      <div class="alert alert-secondary">No slots yet. Add your first one.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr><th>Date</th><th>Time</th><th>Mode / location</th><th>Status</th><th class="text-end">Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($slots as $s): ?>
            <tr>
              <td><?= e(date('M j, Y', strtotime($s['slot_date']))) ?></td>
              <td><?= e(date('g:i A', strtotime($s['start_time']))) ?> - <?= e(date('g:i A', strtotime($s['end_time']))) ?></td>
              <td>
                <?php if (validate_meeting_details((string)($s['meeting_mode'] ?? ''), (string)($s['meeting_details'] ?? '')) === []): ?>
                  <span class="badge text-bg-secondary"><?= e(ucfirst($s['meeting_mode'])) ?></span>
                  <div class="small"><?= e($s['meeting_details']) ?></div>
                <?php else: ?>
                  <span class="text-muted small">Location needed</span>
                <?php endif; ?>
              </td>
              <td><span class="badge text-bg-<?= $badges[$s['status']] ?? 'secondary' ?>"><?= e($s['status']) ?></span></td>
              <td class="text-end">
                <?php if ($s['status'] === 'open'): ?>
                  <a class="btn btn-outline-primary btn-sm" href="slot_edit.php?id=<?= (int)$s['id'] ?>">Edit</a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete this slot?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                  </form>
                <?php else: ?>
                  <span class="text-muted small">Locked</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
