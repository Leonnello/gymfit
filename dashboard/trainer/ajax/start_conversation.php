<?php
session_start();
header('Content-Type: application/json');
require_once '../../../db_connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user1 = $_SESSION['user']['id'];
$user2 = intval($_POST['user2_id']);

// Normalize order (important!)
$u1 = min($user1, $user2);
$u2 = max($user1, $user2);

// Check if conversation exists
$check = $conn->prepare("
    SELECT id FROM conversations 
    WHERE user1_id = ? AND user2_id = ?
");
$check->bind_param("ii", $u1, $u2);
$check->execute();
$res = $check->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode(['conversation_id' => $row['id']]);
    exit;
}

// Create conversation
$insert = $conn->prepare("
    INSERT INTO conversations (user1_id, user2_id, created_at)
    VALUES (?, ?, NOW())
");
$insert->bind_param("ii", $u1, $u2);
$insert->execute();

echo json_encode(['conversation_id' => $conn->insert_id]);
