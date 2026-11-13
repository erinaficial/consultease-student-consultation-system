<?php
// CSRF protection: every form that changes data carries a secret token,
// so another website cannot submit the form on the user's behalf.
require_once __DIR__ . '/auth.php';

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Prints the hidden input to place inside a <form>.
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

// Call at the top of any POST handler.
function csrf_check(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(403);
        exit('Invalid form submission. Please go back and try again.');
    }
}
