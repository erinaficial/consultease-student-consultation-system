<?php
// JSON endpoint for the admin chart: consultations per month (last 6 months) and status.
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/slot_helpers.php';   // also sets the timezone

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

$statuses = ['pending', 'approved', 'completed', 'declined', 'cancelled'];

// The last 6 months, oldest first: ['2026-06' => 'Jun 2026', ...]
$months = [];
$first = new DateTime('first day of this month');
for ($i = 5; $i >= 0; $i--) {
    $m = (clone $first)->modify("-$i months");
    $months[$m->format('Y-m')] = $m->format('M Y');
}
$keys = array_keys($months);

$series = [];
foreach ($statuses as $s) {
    $series[$s] = array_fill(0, count($keys), 0);
}

$rows = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, status, COUNT(*) AS c
     FROM consultations GROUP BY ym, status"
)->fetchAll();

foreach ($rows as $row) {
    $i = array_search($row['ym'], $keys, true);
    if ($i !== false && isset($series[$row['status']])) {
        $series[$row['status']][$i] = (int)$row['c'];
    }
}

echo json_encode(['labels' => array_values($months), 'series' => $series]);
