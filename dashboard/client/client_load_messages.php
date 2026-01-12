<?php
include '../../db_connect.php';
session_start();

// Check session
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit('Unauthorized access');
}

$conversation_id = $_GET['conversation_id'] ?? null;
$user_id = $_SESSION['user']['id'];

if (!$conversation_id) {
    http_response_code(400);
    exit('Missing conversation ID');
}

// ✅ Secure query using prepared statement - only show non-deleted messages or messages deleted by others
$stmt = $conn->prepare("
    SELECT m.id, m.sender_id, m.message, m.created_at, m.is_deleted, m.deleted_by
    FROM messages m 
    WHERE m.conversation_id = ? 
    AND (m.is_deleted = FALSE OR (m.is_deleted = TRUE AND m.deleted_by != ?))
    ORDER BY m.created_at ASC
");
$stmt->bind_param("ii", $conversation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="text-center text-muted py-5">';
    echo '<i class="bi bi-chat-text display-4 d-block mb-3"></i>';
    echo '<p>No messages yet. Start the conversation!</p>';
    echo '</div>';
    exit;
}

while ($row = $result->fetch_assoc()) {
    $is_sent = ($row['sender_id'] == $user_id);
    $message_class = $is_sent ? 'sent' : 'received';
    $is_deleted = $row['is_deleted'];
    
    if ($is_deleted) {
        $message_class .= ' deleted';
    }

    echo '<div class="d-flex ' . ($is_sent ? 'justify-content-end' : 'justify-content-start') . ' mb-3">';
    echo '<div class="message ' . $message_class . ' position-relative">';
    
    if ($is_deleted) {
        echo '<div class="d-flex align-items-center">';
        echo '<i class="bi bi-trash text-muted me-2"></i>';
        echo '<span class="fst-italic">This message was deleted</span>';
        echo '</div>';
    } else {
        echo '<div class="message-content">' . htmlspecialchars($row['message']) . '</div>';
    }
    
    echo '<div class="message-time d-flex align-items-center justify-content-between mt-1">';
    echo '<span>' . date("h:i A", strtotime($row['created_at'])) . '</span>';
    
    // Show delete button only for user's own non-deleted messages
    if ($is_sent && !$is_deleted) {
        echo '<button class="delete-btn ms-2" onclick="deleteMessage(' . $row['id'] . ')">';
        echo '<i class="bi bi-trash"></i>';
        echo '</button>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

$stmt->close();
$conn->close();
?>