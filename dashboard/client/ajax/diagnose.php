<?php
session_start();

// Check if chat server is accessible
$server_url = 'http://localhost:3000';
$ch = curl_init($server_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$server_status = ($response !== false && $http_code !== 0) ? 'running' : 'not_running';

// Check database connection
require_once '../../db_connect.php';

$db_status = 'connected';
$db_test = $conn->query("SHOW TABLES");
if (!$db_test) {
    $db_status = 'error: ' . $conn->error;
}

// Check if messages table exists
$messages_check = $conn->query("SHOW TABLES LIKE 'messages'");
$messages_exists = $messages_check->num_rows > 0 ? 'yes' : 'no';

// Check if conversations table exists
$conversations_check = $conn->query("SHOW TABLES LIKE 'conversations'");
$conversations_exists = $conversations_check->num_rows > 0 ? 'yes' : 'no';

echo json_encode([
    'status' => 'ok',
    'server' => [
        'status' => $server_status,
        'url' => $server_url
    ],
    'database' => [
        'status' => $db_status,
        'messages_table' => $messages_exists,
        'conversations_table' => $conversations_exists
    ],
    'session' => [
        'user_logged_in' => isset($_SESSION['user']) ? 'yes' : 'no',
        'user_id' => $_SESSION['user']['id'] ?? null
    ]
]);
?>
