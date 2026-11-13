<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';

// Already logged in? Go straight to the dashboard.
if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

// One-time message from the registration page
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, full_name, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // password_verify compares the typed password with the stored hash
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);   // new session id after login (prevents session fixation)
        $_SESSION['user'] = [
            'id'        => (int)$user['id'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
        ];
        header('Location: dashboard.php');
        exit;
    }
    // Same message for wrong email or wrong password, so attackers learn nothing
    $error = 'Invalid email or password.';
}

$pageTitle = 'Login';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-8 col-lg-5">
    <div class="auth-brand"><?php include __DIR__ . '/../includes/logo.php'; ?>ConsultEase</div>
    <h1 class="h3 mb-1">Welcome back</h1>
    <p class="text-muted">Sign in to manage consultations.</p>

    <?php if ($flash): ?>
      <div class="alert alert-success" role="alert"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.edu"
               value="<?= e($email) ?>" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password"
               placeholder="Enter your password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Sign in</button>
    </form>
    <p class="small text-muted mt-3 mb-2">Your account role directs you to the student, faculty, or administrator dashboard.</p>
    <p class="small mb-0">No account yet? <a href="register.php">Register as a student</a></p>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
