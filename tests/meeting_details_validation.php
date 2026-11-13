<?php
require __DIR__ . '/../includes/slot_helpers.php';

if (!function_exists('validate_meeting_details')) {
    fwrite(STDERR, "FAIL: validate_meeting_details() is not implemented.\n");
    exit(1);
}

$cases = [
    ['onsite', 'Room 201', true],
    ['onsite', '   ', false],
    ['onsite', str_repeat('x', 2048), true],
    ['onsite', str_repeat('x', 2049), false],
    ['online', 'https://meet.example.edu/room', true],
    ['online', 'http://meet.example.edu/room', false],
    ['online', 'https://', false],
    ['online', '   ', false],
    ['other', 'Room 201', false],
];

foreach ($cases as [$mode, $details, $expectedValid]) {
    $actualValid = validate_meeting_details($mode, $details) === [];
    if ($actualValid !== $expectedValid) {
        fwrite(STDERR, "FAIL: Unexpected validation result for mode: {$mode}\n");
        exit(1);
    }
}

echo "All meeting-details validation cases passed.\n";
