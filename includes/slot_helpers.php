<?php
// Shared rules for adding and editing availability slots.
date_default_timezone_set('Asia/Manila');

// ---- School hours (change these three values to match your school) ----
const SCHOOL_OPEN  = '08:00';          // earliest start time
const SCHOOL_CLOSE = '17:00';          // latest end time
const SCHOOL_DAYS  = [1, 2, 3, 4, 5, 6];   // 1 = Monday ... 7 = Sunday (here: Monday to Saturday)

// "8:00 AM to 5:00 PM", used in hints and error messages
function school_hours_text(): string {
    return date('g:i A', strtotime(SCHOOL_OPEN)) . ' to ' . date('g:i A', strtotime(SCHOOL_CLOSE));
}

function validate_meeting_details(string $mode, string $details): array {
    if (!in_array($mode, ['onsite', 'online'], true)) {
        return ['Please choose Onsite or Online.'];
    }

    $details = trim($details);
    if ($details === '') {
        return ['Please enter a room/location or meeting link.'];
    }
    if (mb_strlen($details) > 2048) {
        return ['Meeting details must be 2,048 characters or fewer.'];
    }
    if ($mode === 'online') {
        $scheme = parse_url($details, PHP_URL_SCHEME);
        if (!filter_var($details, FILTER_VALIDATE_URL) ||
            !is_string($scheme) ||
            strtolower($scheme) !== 'https') {
            return ['Please enter a valid HTTPS meeting link.'];
        }
    }

    return [];
}

// Returns a list of error messages (empty list = the slot is valid).
function validate_slot(PDO $pdo, int $facultyId, string $date, string $start, string $end, ?int $ignoreId = null): array {
    $errors = [];

    // Date must be a real date, not in the past, and a school day
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        $errors[] = 'Please choose a valid date.';
    } elseif ($date < date('Y-m-d')) {
        $errors[] = 'The date cannot be in the past.';
    } elseif (!in_array((int)$d->format('N'), SCHOOL_DAYS, true)) {
        $errors[] = 'Slots can only be set on school days (Monday to Saturday).';
    }

    // Times must look like HH:MM, the end must be after the start, and both must be inside school hours
    if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
        $errors[] = 'Please choose valid start and end times.';
    } elseif ($end <= $start) {
        $errors[] = 'End time must be later than the start time.';
    } elseif ($start < SCHOOL_OPEN || $end > SCHOOL_CLOSE) {
        $errors[] = 'Slots must be within school hours (' . school_hours_text() . ').';
    }

    // No overlap with another slot of the same faculty on the same day
    if (!$errors) {
        $sql = "SELECT id FROM availability_slots
                WHERE faculty_id = ? AND slot_date = ? AND status <> 'cancelled'
                  AND start_time < ? AND end_time > ?";
        $params = [$facultyId, $date, $end, $start];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors[] = 'This time overlaps with another one of your slots.';
        }
    }
    return $errors;
}
