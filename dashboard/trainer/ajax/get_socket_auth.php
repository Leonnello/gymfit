<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$user_id = $_SESSION['user']['id'];

// Get token for Socket.IO authentication
$token = bin2hex(random_bytes(32));

// Store token in cache/session
$_SESSION['chat_token'] = $token;
$_SESSION['chat_token_time'] = time();

echo json_encode([
    'success' => true,
    'user_id' => $user_id,
    'token' => $token,
    'server_url' => 'http://localhost:3000'
]);
?>
