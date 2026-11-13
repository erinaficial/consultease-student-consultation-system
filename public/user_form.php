<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';

require_role('admin');
$me = current_user();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;
$row = null;

if ($editing) {
    $stmt = $pdo->prepare('SELECT id, full_name, email, role, department FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        $_SESSION['flash'] = 'That user was not found.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: users.php');
        exit;
    }
}

$errors = [];
$name  = $row['full_name'] ?? '';
$email = $row['email'] ?? '';
$role  = $row['role'] ?? 'student';
$dept  = $row['department'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? 'student';
    $dept     = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';

    // An admin cannot change their own role (so the system always keeps an admin)
    if ($editing && $id === (int)$me['id']) {
        $role = $row['role'];
    }

    if ($name === '' || $email === '') {
        $errors[] = 'Please complete all required fields.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!in_array($role, ['student', 'faculty', 'admin'], true)) {
        $errors[] = 'Please choose a valid role.';
    }
    if (!$editing && $password === '') {
        $errors[] = 'A password is required for a new user.';
    }
    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    // Email must be unique (ignoring this user's own row when editing)
    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            $errors[] = 'That email is already used by another account.';
        }
    }

    if (!$errors) {
        if ($editing) {
            // UPDATE (the password changes only if a new one was typed)
            $sql = 'UPDATE users SET full_name = ?, email = ?, role = ?, department = ?';
            $params = [$name, $email, $role, $dept];
            if ($password !== '') {
                $sql .= ', password_hash = ?';
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = ?';
            $params[] = $id;
            $pdo->prepare($sql)->execute($params);
            if ($id === (int)$me['id']) {
                $_SESSION['user']['full_name'] = $name;
            }
            $_SESSION['flash'] = 'User updated.';
        } else {
            // CREATE
            $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, department) VALUES (?, ?, ?, ?, ?)'
            )->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $dept]);
            $_SESSION['flash'] = 'User created.';
        }
        header('Location: users.php');
        exit;
    }
}

// Existing departments, offered as suggestions so names stay consistent
$departments = $pdo->query(
    "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department <> '' ORDER BY department"
)->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = $editing ? 'Edit User' : 'Add User';
require __DIR__ . '/../includes/header.php';
?>
<div class="row">
  <div class="col-lg-6">
    <h1 class="h3"><?= $editing ? 'Edit user' : 'Add user' ?></h1>

    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <div class="mb-3">
        <label for="full_name" class="form-label">Full name</label>
        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($name) ?>" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required>
      </div>
      <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <select class="form-select" id="role" name="role" <?= ($editing && $id === (int)$me['id']) ? 'disabled' : '' ?>>
          <?php foreach (['student', 'faculty', 'admin'] as $r): ?>
            <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($editing && $id === (int)$me['id']): ?>
          <div class="form-text">You cannot change your own role.</div>
        <?php endif; ?>
      </div>
      <div class="mb-3">
        <label for="department" class="form-label">Department</label>
        <input type="text" class="form-control" id="department" name="department" list="dept-list" value="<?= e($dept) ?>">
        <datalist id="dept-list">
          <?php foreach ($departments as $d): ?><option value="<?= e($d) ?>"><?php endforeach; ?>
        </datalist>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label"><?= $editing ? 'New password (leave blank to keep the current one)' : 'Password (at least 8 characters)' ?></label>
        <input type="password" class="form-control" id="password" name="password" minlength="8" <?= $editing ? '' : 'required' ?>>
      </div>
      <button type="submit" class="btn btn-primary"><?= $editing ? 'Save changes' : 'Create user' ?></button>
      <a class="btn btn-outline-secondary" href="users.php">Cancel</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
