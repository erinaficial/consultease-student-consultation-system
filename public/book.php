<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/consult_helpers.php';

require_role('student');
$user = current_user();

$slotId = (int)($_GET['slot_id'] ?? $_POST['slot_id'] ?? 0);

// Load the slot so we can show it (only if it is still open and not in the past)
$stmt = $pdo->prepare(
    "SELECT s.id, s.faculty_id, s.slot_date, s.start_time, s.end_time, s.meeting_mode, s.meeting_details,
            u.full_name, u.department
     FROM availability_slots s
     JOIN users u ON u.id = s.faculty_id
     WHERE s.id = ? AND s.status = 'open' AND s.slot_date >= ?
       AND s.meeting_mode IN ('onsite', 'online')
       AND s.meeting_details IS NOT NULL AND TRIM(s.meeting_details) <> ''"
);
$stmt->execute([$slotId, date('Y-m-d')]);
$slot = $stmt->fetch();

if (!$slot || validate_meeting_details(
    (string)$slot['meeting_mode'],
    (string)($slot['meeting_details'] ?? '')
) !== []) {
    $_SESSION['flash'] = 'That slot is no longer available.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: browse.php');
    exit;
}

$label = slot_label($slot['slot_date'], $slot['start_time'], $slot['end_time']);
$error = '';
$topic = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $topic = trim($_POST['topic'] ?? '');

    if ($topic === '') {
        $error = 'Please enter a consultation topic.';
    } elseif (mb_strlen($topic) > 255) {
        $error = 'The topic must be 255 characters or fewer.';
    } else {
        try {
            // TRANSACTION: either every step below succeeds, or none of them is saved.
            $pdo->beginTransaction();

            // FOR UPDATE locks this slot row, so two students cannot book it at the same moment.
            $lock = $pdo->prepare(
                "SELECT id, meeting_mode, meeting_details FROM availability_slots
                 WHERE id = ? AND status = 'open'
                   AND meeting_mode IN ('onsite', 'online')
                   AND meeting_details IS NOT NULL AND TRIM(meeting_details) <> ''
                 FOR UPDATE"
            );
            $lock->execute([$slotId]);
            $lockedSlot = $lock->fetch();
            if (!$lockedSlot || validate_meeting_details(
                (string)$lockedSlot['meeting_mode'],
                (string)($lockedSlot['meeting_details'] ?? '')
            ) !== []) {
                $pdo->rollBack();
                $_SESSION['flash'] = 'Sorry, someone just booked that slot. Please choose another one.';
                $_SESSION['flash_type'] = 'warning';
                header('Location: browse.php');
                exit;
            }

            // 1. Save the consultation (starts as 'pending')
            $ins = $pdo->prepare(
                'INSERT INTO consultations (slot_id, student_id, faculty_id, topic, slot_label) VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute([$slotId, $user['id'], $slot['faculty_id'], $topic, $label]);

            // 2. Mark the slot as booked
            $upd = $pdo->prepare("UPDATE availability_slots SET status = 'booked' WHERE id = ?");
            $upd->execute([$slotId]);

            // 3. Tell the faculty member
            notify($pdo, (int)$slot['faculty_id'], $user['full_name'] . ' requested a consultation on ' . $label . '.');

            $pdo->commit();
            $_SESSION['flash'] = 'Booking sent. Please wait for the faculty member to approve it.';
            header('Location: my_consultations.php');
            exit;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Could not save your booking. Please try again.';
        }
    }
}

$pageTitle = 'Book a Slot';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <h1 class="h3 mb-3">Book a consultation</h1>

    <div class="card mb-3">
      <div class="card-body">
        <div class="fw-semibold"><?= e($slot['full_name']) ?></div>
        <div class="text-muted small"><?= e($slot['department'] ?? '') ?></div>
        <div class="mt-2"><?= e($label) ?></div>
        <div class="mt-2"><span class="badge text-bg-secondary"><?= e(ucfirst($slot['meeting_mode'])) ?></span></div>
        <div class="small text-muted mt-2">The exact room or meeting link will be available after approval.</div>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="slot_id" value="<?= (int)$slotId ?>">
      <div class="mb-3">
        <label for="topic" class="form-label">What would you like to discuss?</label>
        <input type="text" class="form-control" id="topic" name="topic" maxlength="255"
               value="<?= e($topic) ?>" required>
      </div>
      <button type="submit" class="btn btn-primary">Confirm booking</button>
      <a class="btn btn-outline-secondary" href="browse.php">Back</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
