<?php
// Shared top of every page: <head>, top bar, and (when logged in) the left sidebar.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
$user = current_user();

// Color palette: 'forest' (green and gold), 'navy' (navy and gold) or 'maroon' (maroon and gold)
$palette = 'forest';

$scriptName = basename($_SERVER['SCRIPT_NAME']);                              // e.g. "slots.php"
$pageClass  = 'page-' . preg_replace('/[^a-z0-9_-]/i', '', basename($scriptName, '.php'));

// One sidebar link; the link of the page you are on (or one of its sub-pages) gets the "active" style.
if (!function_exists('nav_item')) {
    function nav_item(string $href, string $icon, string $label, string $current, array $also = []): string {
        $active = ($current === $href) || in_array($current, $also, true);
        return '<a class="side-link' . ($active ? ' active' : '') . '"'
             . ($active ? ' aria-current="page"' : '')
             . ' href="' . e($href) . '"><span class="side-icon" aria-hidden="true">' . $icon . '</span>'
             . e($label) . '</a>';
    }
}

$menu = [];
if ($user) {
    $menu[] = nav_item('dashboard.php', '🏠', 'Dashboard', $scriptName);
    if ($user['role'] === 'student') {
        $menu[] = nav_item('browse.php', '🔎', 'Find Faculty', $scriptName, ['book.php']);
        $menu[] = nav_item('my_consultations.php', '📋', 'My Consultations', $scriptName);
    } elseif ($user['role'] === 'faculty') {
        $menu[] = nav_item('slots.php', '🗓️', 'My Availability', $scriptName, ['slot_edit.php']);
        $menu[] = nav_item('requests.php', '📥', 'Requests', $scriptName);
    } else {
        $menu[] = nav_item('users.php', '👥', 'Manage Users', $scriptName, ['user_form.php']);
        $menu[] = nav_item('reports.php', '📊', 'Reports', $scriptName);
    }
    $menu[] = nav_item('notifications.php', '🔔', 'Notifications', $scriptName);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-palette="<?= e($palette) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle ?? 'ConsultEase') ?> | ConsultEase</title>
  <link rel="icon" type="image/svg+xml" href="images/favicon-<?= e($palette) ?>.svg">
  <script>
    // Apply the saved theme BEFORE the page paints, so there is no white flash in dark mode.
    try {
      var t = localStorage.getItem('theme') ||
              (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', t);
    } catch (err) {}
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Source+Serif+4:wght@600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/theme.css?v=18">
</head>
<body class="d-flex flex-column min-vh-100 <?= e($pageClass) ?>">
<nav class="navbar app-navbar">
  <div class="container-fluid px-3 flex-nowrap gap-2">
    <div class="d-flex align-items-center gap-2">
      <?php if ($user): ?>
        <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-label="Open menu">☰</button>
      <?php endif; ?>
      <a class="navbar-brand m-0" href="<?= $user ? 'dashboard.php' : 'index.php' ?>"><?php include __DIR__ . '/logo.php'; ?>ConsultEase</a>
    </div>

    <div class="d-flex align-items-center gap-2">
      <?php if ($user): ?>
        <span class="role-pill d-none d-sm-inline"><?= e($user['role']) ?></span>
      <?php endif; ?>
      <button type="button" id="theme-toggle" class="btn btn-outline-secondary btn-sm">🌙 Dark</button>

      <?php if ($user): ?>
        <div class="dropdown" id="notif-root">
          <button class="btn btn-outline-secondary btn-sm position-relative" type="button"
                  data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                  aria-label="Notifications">
            🔔
            <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-0" style="width: 320px; max-width: 90vw;">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
              <strong class="small">Notifications</strong>
              <button type="button" id="notif-mark" class="btn btn-link btn-sm p-0">Mark all as read</button>
            </div>
            <ul id="notif-list" class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;"></ul>
          </div>
        </div>
        <span class="small user-label d-none d-xl-inline"><?= e($user['full_name']) ?></span>
        <a class="btn btn-primary btn-sm" href="logout.php">Logout</a>
      <?php else: ?>
        <a class="btn btn-outline-primary btn-sm" href="login.php">Login</a>
        <a class="btn btn-primary btn-sm" href="register.php">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="app-shell flex-grow-1">
<?php if ($user): ?>
  <aside class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarLabel">
    <div class="offcanvas-header d-lg-none">
      <span class="fw-bold" id="sidebarLabel">Menu</span>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3">
      <nav class="side-nav" aria-label="Main menu"><?= implode('', $menu) ?></nav>
    </div>
  </aside>
<?php endif; ?>
<main class="<?= $user ? 'app-content p-3 p-lg-4' : 'container py-4' ?>">
