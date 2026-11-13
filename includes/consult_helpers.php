<?php
// Small helpers shared by the booking and approval pages.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/slot_helpers.php';   // also sets the timezone

// Adds a notification for a user (the notification bell is built in the next step).
function notify(PDO $pdo, int $userId, string $message): void {
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
    $stmt->execute([$userId, $message]);
}

// "Nov 10, 2026, 2:00 PM - 3:00 PM"
function slot_label(string $date, string $start, string $end): string {
    return date('M j, Y', strtotime($date)) . ', '
         . date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end));
}

// A colored status badge
function status_badge(string $status): string {
    $map = ['pending' => 'warning', 'approved' => 'success', 'declined' => 'danger',
            'completed' => 'primary', 'cancelled' => 'secondary'];
    return '<span class="badge text-bg-' . ($map[$status] ?? 'secondary') . '">' . e($status) . '</span>';
}
