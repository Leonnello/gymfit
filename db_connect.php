<?php
$host = $_SERVER['HTTP_HOST'];
$hostname = gethostname();

// Treat localhost, loopback, local IPs, ngrok, and machine hostname as LOCAL
if (
    $host === 'localhost' ||
    $host === '127.0.0.1' ||
    strpos($host, 'localhost:') === 0 ||
    strpos($host, '127.0.0.1:') === 0 ||
    str_contains($host, 'ngrok') ||
    str_contains($host, $hostname) ||
    preg_match('/^192\.168\./', $host) || // Private IP range 192.168.x.x
    preg_match('/^10\./', $host) || // Private IP range 10.x.x.x
    preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host) // Private IP range 172.16.x.x - 172.31.x.x
) {
    require_once __DIR__ . '/db_local.php';
} else {
    require_once __DIR__ . '/db_hosting.php';
}
?>
