<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';

require_role('admin');
$user = current_user();

// One-time message
$flash = $_SESSION['flash'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id === (int)$user['id']) {
            $_SESSION['flash'] = 'You cannot delete your own account.';
            $_SESSION['flash_type'] = 'warning';
        } else {
            try {
                $pdo->beginTransaction();
                // If the user is a student, free the slots they had booked
                $pdo->prepare(
                    "UPDATE availability_slots s
                     JOIN consultations c ON c.slot_id = s.id
                     SET s.status = 'open'
                     WHERE c.student_id = ? AND c.status IN ('pending', 'approved')"
                )->execute([$id]);
                $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $del->execute([$id]);
                $pdo->commit();
                $_SESSION['flash'] = $del->rowCount() > 0 ? 'User deleted.' : 'User not found.';
                if ($del->rowCount() === 0) {
                    $_SESSION['flash_type'] = 'warning';
                }
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['flash'] = 'Could not delete the user. Please try again.';
                $_SESSION['flash_type'] = 'warning';
            }
        }
    }
    header('Location: users.php');
    exit;
}

// READ with filters
$role = $_GET['role'] ?? '';
$q    = trim($_GET['q'] ?? '');
$where = [];
$params = [];
if (in_array($role, ['student', 'faculty', 'admin'], true)) {
    $where[] = 'role = ?';
    $params[] = $role;
} else {
    $role = '';
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(full_name LIKE ? OR email LIKE ? OR department LIKE ?)';
    array_push($params, $like, $like, $like);
}
$sql = 'SELECT id, full_name, email, role, department, created_at FROM users'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY FIELD(role, "admin", "faculty", "student"), full_name LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roleBadges = ['admin' => 'danger', 'faculty' => 'primary', 'student' => 'secondary'];

$pageTitle = 'Manage Users';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
  <div>
    <h1 class="h3">Manage users</h1>
    <p class="page-subtitle">Create faculty and admin accounts, and edit or remove users.</p>
  </div>
  <a class="btn btn-primary" href="user_form.php">Add user</a>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flashType) ?>" role="alert"><?= e($flash) ?></div>
<?php endif; ?>

<form method="get" class="row g-2 mb-3" role="search">
  <div class="col-md-5">
    <label for="q" class="visually-hidden">Search users</label>
    <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Search name, email or department">
  </div>
  <div class="col-md-3">
    <label for="role" class="visually-hidden">Role</label>
    <select class="form-select" id="role" name="role">
      <option value="">All roles</option>
      <?php foreach (['student', 'faculty', 'admin'] as $r): ?>
        <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Filter</button></div>
</form>

<?php if (!$users): ?>
  <div class="alert alert-secondary">No users match your filters.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['full_name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="badge text-bg-<?= $roleBadges[$u['role']] ?? 'secondary' ?>"><?= e($u['role']) ?></span></td>
          <td><?= e($u['department'] ?? '') ?></td>
          <td class="text-end">
            <a class="btn btn-outline-primary btn-sm" href="user_form.php?id=<?= (int)$u['id'] ?>">Edit</a>
            <?php if ((int)$u['id'] !== (int)$user['id']): ?>
              <form method="post" class="d-inline"
                    onsubmit="return confirm('Delete this user? Their slots or bookings will be removed too.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
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
