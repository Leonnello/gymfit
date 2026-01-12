<?php
session_start();
include "../../../db.php";

$user_id = $_SESSION['user_id'];
$conversation_id = $_GET['conversation_id'];

$stmt = $conn->prepare("
    SELECT m.*, u.firstName, u.lastName
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC
");
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);
