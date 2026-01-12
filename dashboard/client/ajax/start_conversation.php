<?php
session_start();
require_once '../../../db_connect.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'no_session']);
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$user2_id = (int)($_POST['user2_id'] ?? 0);

if (!$user2_id) {
    echo json_encode(['error' => 'no_user']);
    exit;
}

/* Check if conversation already exists */
$stmt = $conn->prepare("
    SELECT id FROM conversations
    WHERE (user1_id = ? AND user2_id = ?)
       OR (user1_id = ? AND user2_id = ?)
    LIMIT 1
");
$stmt->bind_param("iiii", $user_id, $user2_id, $user2_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo json_encode(['conversation_id' => $row['id']]);
    exit;
}

/* Create conversation */
$insert = $conn->prepare("
    INSERT INTO conversations (user1_id, user2_id, created_at)
    VALUES (?, ?, NOW())
");
$insert->bind_param("ii", $user_id, $user2_id);
$insert->execute();

echo json_encode(['conversation_id' => $conn->insert_id]);
