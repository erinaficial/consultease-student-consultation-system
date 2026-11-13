<?php
require __DIR__ . '/../includes/auth.php';

$_SESSION = [];       // clear all session data
session_destroy();    // end the session
header('Location: login.php');
exit;
