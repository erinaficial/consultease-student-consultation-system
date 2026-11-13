<?php
// JSON endpoint for the live search on the "Find Faculty" page.
//   GET ?q=text&department=name&date=YYYY-MM-DD  -> { slots: [...] }
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/slot_helpers.php';   // also sets the timezone

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user || !in_array($user['role'], ['student', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

$q    = trim($_GET['q'] ?? '');
$dept = trim($_GET['department'] ?? '');
$date = trim($_GET['date'] ?? '');

$sql = "SELECT s.id, s.faculty_id, s.slot_date, s.start_time, s.end_time, s.meeting_mode, u.full_name, u.department
        FROM availability_slots s
        JOIN users u ON u.id = s.faculty_id
        WHERE s.status = 'open' AND s.slot_date >= ?
          AND s.meeting_mode IN ('onsite', 'online')
          AND s.meeting_details IS NOT NULL AND TRIM(s.meeting_details) <> ''";
$params = [date('Y-m-d')];

if ($q !== '') {
    // Escape LIKE wildcards typed by the user, then search name OR department
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $sql .= ' AND (u.full_name LIKE ? OR u.department LIKE ?)';
    $params[] = $like;
    $params[] = $like;
}

if ($dept !== '') {
    $sql .= ' AND u.department = ?';
    $params[] = $dept;
}

if ($date !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d && $d->format('Y-m-d') === $date) {
        $sql .= ' AND s.slot_date = ?';
        $params[] = $date;
    }
}

$sql .= ' ORDER BY u.full_name, s.slot_date, s.start_time LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$slots = [];
foreach ($stmt->fetchAll() as $row) {
    $slots[] = [
        'id'         => (int)$row['id'],
        'faculty_id' => (int)$row['faculty_id'],
        'faculty'    => $row['full_name'],
        'department' => $row['department'] ?? '',
        'meeting_mode' => $row['meeting_mode'],
        'date_label' => date('D, M j', strtotime($row['slot_date'])),
        'time_label' => date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])),
    ];
}
echo json_encode(['slots' => $slots]);
