<?php
// Database connection using PDO (prepared statements help prevent SQL injection).
$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    // Show every timestamp in Philippine time (UTC+8), locally and on the host
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    http_response_code(500);
    // Technical details are shown only while 'debug' is true; visitors never see them online
    exit(!empty($config['debug']) ? 'Database error: ' . $e->getMessage() : 'Unable to connect to the database.');
}
