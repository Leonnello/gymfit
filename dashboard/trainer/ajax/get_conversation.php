<?php
session_start();
require_once '../../../db_connect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$user_id = $_SESSION['user']['id'];

/*
 Fetch conversations where the logged-in user is a participant.
 Get the OTHER user's info + last message.
*/
$stmt = $conn->prepare("
    SELECT 
        c.id AS conversation_id,
        c.last_message,
        c.last_message_at,
        u.id AS other_user_id,
        u.firstName,
        u.lastName
    FROM conversations c
    JOIN users u 
        ON u.id = IF(c.user1_id = ?, c.user2_id, c.user1_id)
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY c.last_message_at DESC, c.created_at DESC
");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);

/* THEN THIS */
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];

while ($row = $result->fetch_assoc()) {
    $conversations[] = [
        'conversation_id' => $row['conversation_id'],
        'other_user_id'   => $row['other_user_id'],
        'name'            => $row['firstName'] . ' ' . $row['lastName'],
        'last_message'    => $row['last_message'] ?? 'No messages yet',
        'last_time'       => $row['last_message_at']
            ? date('M d, H:i', strtotime($row['last_message_at']))
            : ''
    ];
}

header('Content-Type: application/json');
echo json_encode($conversations);
