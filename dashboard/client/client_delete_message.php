<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit('Unauthorized access');
}

$user_id = $_SESSION['user']['id'];
$message_id = $_POST['message_id'] ?? null;

if (!$message_id) {
    http_response_code(400);
    exit('Missing message ID');
}

// Check if message exists and belongs to user
$check_stmt = $conn->prepare("SELECT id FROM messages WHERE id = ? AND sender_id = ?");
$check_stmt->bind_param("ii", $message_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    http_response_code(403);
    exit('You can only delete your own messages');
}

// Soft delete the message
$delete_stmt = $conn->prepare("UPDATE messages SET is_deleted = TRUE, deleted_by = ?, deleted_at = NOW() WHERE id = ?");
$delete_stmt->bind_param("ii", $user_id, $message_id);

if ($delete_stmt->execute()) {
    echo "Message deleted successfully";
} else {
    http_response_code(500);
    echo "Error deleting message";
}

$check_stmt->close();
$delete_stmt->close();
$conn->close();
?>