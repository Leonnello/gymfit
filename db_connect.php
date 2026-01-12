<?php
$host = $_SERVER['HTTP_HOST'];

// Treat localhost AND ngrok as LOCAL
if (
    $host === 'localhost' ||
    $host === '127.0.0.1' ||
    str_contains($host, 'ngrok')
) {
    require_once __DIR__ . '/db_local.php';
} else {
    require_once __DIR__ . '/db_hosting.php';
}
?>
