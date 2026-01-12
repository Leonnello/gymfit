<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit('Unauthorized access');
}

$user_id = $_SESSION['user']['id'];
$conversation_id = $_POST['conversation_id'] ?? null;

if (!$conversation_id) {
    http_response_code(400);
    exit('Missing conversation ID');
}

// Check if user is part of the conversation
$check_stmt = $conn->prepare("SELECT id FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
$check_stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    http_response_code(403);
    exit('You are not part of this conversation');
}

// Soft delete all user's messages in this conversation
$delete_stmt = $conn->prepare("UPDATE messages SET is_deleted = TRUE, deleted_by = ?, deleted_at = NOW() WHERE conversation_id = ? AND sender_id = ?");
$delete_stmt->bind_param("iii", $user_id, $conversation_id, $user_id);

if ($delete_stmt->execute()) {
    echo "Chat cleared successfully";
} else {
    http_response_code(500);
    echo "Error clearing chat";
}

$check_stmt->close();
$delete_stmt->close();
$conn->close();
?>