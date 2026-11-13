<?php
// JSON endpoint for the notification bell.
//   GET  -> { unread: number, items: [latest 10 notifications] }
//   POST -> marks all of the logged-in user's notifications as read
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user['id']]);
    echo json_encode(['ok' => true]);
    exit;
}

$count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$count->execute([$user['id']]);

$list = $pdo->prepare(
    'SELECT id, message, is_read, created_at FROM notifications
     WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 10'
);
$list->execute([$user['id']]);

echo json_encode([
    'unread' => (int)$count->fetchColumn(),
    'items'  => $list->fetchAll(),
]);
