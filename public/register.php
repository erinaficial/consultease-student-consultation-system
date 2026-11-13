<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';

$errors = [];
$name = $email = $department = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    // 1. Server-side validation (never trust the browser alone)
    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'Please complete all required fields.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // 2. Is the email already used? (prepared statement -> no SQL injection)
    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'That email is already registered.';
        }
    }

    // 3. Save the user. The role is fixed to 'student' here, NOT taken from the form.
    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, department) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'student', $department]);
        $_SESSION['flash'] = 'Account created. You can now log in.';
        header('Location: login.php');
        exit;
    }
}

$pageTitle = 'Register';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-8 col-lg-5">
    <h1 class="h3 mb-3">Create a student account</h1>

    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label for="full_name" class="form-label">Full name</label>
        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($name) ?>" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required>
      </div>
      <div class="mb-3">
        <label for="department" class="form-label">Department / Course</label>
        <input type="text" class="form-control" id="department" name="department" value="<?= e($department) ?>">
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password (at least 8 characters)</label>
        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
      </div>
      <div class="mb-3">
        <label for="confirm_password" class="form-label">Confirm password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
    <p class="mt-3 small">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
