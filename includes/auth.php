<?php
// Starts the session and provides helper functions used by every page.

// Show PHP errors only while developing. On the live site, set 'debug' => false in config.php,
// so visitors never see file paths or technical details.
$appConfig = require __DIR__ . '/config.php';
ini_set('display_errors', !empty($appConfig['debug']) ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,                       // JavaScript cannot read the session cookie
        'samesite' => 'Lax',                      // helps block cross-site requests
        'secure'   => !empty($_SERVER['HTTPS']),  // sent only over HTTPS when the site uses HTTPS
    ]);
    session_start();
}

// Escape output so user input can never run as HTML/JavaScript (prevents XSS).
function e(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Returns the logged-in user's data from the session, or null if nobody is logged in.
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

// Sends visitors who are not logged in to the login page.
function require_login(): void {
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

// Access control done on the SERVER: only the listed roles may continue.
function require_role(string ...$roles): void {
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}
