<?php
session_start();
require_once '../../../db_connect.php';

if (!isset($_SESSION['user'])) {
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$conversation_id = (int)($_GET['conversation_id'] ?? 0);

if (!$conversation_id) {
    echo json_encode([]);
    exit;
}

/* Security check */
$check = $conn->prepare("
    SELECT id FROM conversations
    WHERE id = ?
      AND (user1_id = ? OR user2_id = ?)
");
$check->bind_param("iii", $conversation_id, $user_id, $user_id);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode([]);
    exit;
}

/* Fetch messages */
$stmt = $conn->prepare("
    SELECT sender_id, message, created_at
    FROM messages
    WHERE conversation_id = ?
    ORDER BY created_at ASC
");
$stmt->bind_param("i", $conversation_id);
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
