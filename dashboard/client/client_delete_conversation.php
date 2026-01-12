<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit('Unauthorized access');
}

$user_id = $_SESSION['user']['id'];
$conversation_id = $_POST['conversation_id'] ?? null;
$user1_id = $_POST['user1_id'] ?? null;
$user2_id = $_POST['user2_id'] ?? null;

if (!$conversation_id || !$user1_id || !$user2_id) {
    http_response_code(400);
    exit('Missing required parameters');
}

// Check if user is part of the conversation
if ($user_id != $user1_id && $user_id != $user2_id) {
    http_response_code(403);
    exit('You are not part of this conversation');
}

// Check if deleted_by columns exist
$check_columns = $conn->query("SHOW COLUMNS FROM conversations LIKE 'deleted_by_user1'");
$columns_exist = $check_columns->num_rows > 0;

if ($columns_exist) {
    // Soft delete: mark the conversation as deleted for this user
    if ($user_id == $user1_id) {
        $stmt = $conn->prepare("UPDATE conversations SET deleted_by_user1 = TRUE WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE conversations SET deleted_by_user2 = TRUE WHERE id = ?");
    }
    
    $stmt->bind_param("i", $conversation_id);
    
    if ($stmt->execute()) {
        echo "Conversation deleted successfully";
    } else {
        http_response_code(500);
        echo "Error deleting conversation";
    }
    
    $stmt->close();
} else {
    // If columns don't exist, let's create them first
    $conn->query("ALTER TABLE conversations ADD COLUMN deleted_by_user1 BOOLEAN DEFAULT FALSE");
    $conn->query("ALTER TABLE conversations ADD COLUMN deleted_by_user2 BOOLEAN DEFAULT FALSE");
    
    // Now perform the soft delete
    if ($user_id == $user1_id) {
        $stmt = $conn->prepare("UPDATE conversations SET deleted_by_user1 = TRUE WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE conversations SET deleted_by_user2 = TRUE WHERE id = ?");
    }
    
    $stmt->bind_param("i", $conversation_id);
    
    if ($stmt->execute()) {
        echo "Conversation deleted successfully";
    } else {
        http_response_code(500);
        echo "Error deleting conversation";
    }
    
    $stmt->close();
}

$conn->close();
?>