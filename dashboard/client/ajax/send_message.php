<?php
session_start();
require_once '../../../db_connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$conversation_id = (int)($_POST['conversation_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!$conversation_id || $message === '') {
    echo json_encode(['error' => 'Invalid']);
    exit;
}

/* Ensure client belongs to conversation */
$check = $conn->prepare("
    SELECT id FROM conversations
    WHERE id = ?
      AND (user1_id = ? OR user2_id = ?)
");
$check->bind_param("iii", $conversation_id, $user_id, $user_id);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

/* Insert message */
$stmt = $conn->prepare("
    INSERT INTO messages (conversation_id, sender_id, message, created_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->bind_param("iis", $conversation_id, $user_id, $message);
$stmt->execute();

/* Update conversation preview */
$upd = $conn->prepare("
    UPDATE conversations
    SET last_message = ?, last_message_at = NOW()
    WHERE id = ?
");
$upd->bind_param("si", $message, $conversation_id);
$upd->execute();

echo json_encode(['success' => true]);
