<?php
session_start();
require_once '../../../db_connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$user_id = (int) $_SESSION['user']['id'];

if (!isset($_GET['conversation_id'])) {
    echo json_encode([]);
    exit;
}

$conversation_id = (int) $_GET['conversation_id'];

/* =========================
   SECURITY CHECK
========================= */
$check = $conn->prepare("
    SELECT id 
    FROM conversations 
    WHERE id = ? 
      AND (user1_id = ? OR user2_id = ?)
");
$check->bind_param("iii", $conversation_id, $user_id, $user_id);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode([]);
    exit;
}

/* =========================
   FETCH MESSAGES
========================= */
$stmt = $conn->prepare("
    SELECT 
        id,
        sender_id,
        message,
        created_at
    FROM messages
    WHERE conversation_id = ?
    ORDER BY created_at ASC
");
$stmt->bind_param("i", $conversation_id);
$stmt->execute();

$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
exit;
