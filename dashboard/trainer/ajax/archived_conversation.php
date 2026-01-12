<?php
session_start();
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user']['id'];

if (!isset($_POST['conversation_ids']) || empty($_POST['conversation_ids'])) {
    echo json_encode(['success' => false, 'message' => 'No conversation selected']);
    exit;
}

// Convert comma-separated IDs to array of integers
$conversation_ids = array_map('intval', explode(',', $_POST['conversation_ids']));
if (empty($conversation_ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid conversation IDs']);
    exit;
}

// Ensure the conversations belong to the current user
$ids_placeholder = implode(',', array_fill(0, count($conversation_ids), '?'));

$sql = "UPDATE conversations 
        SET is_archived = 1 
        WHERE id IN ($ids_placeholder) 
        AND (user1_id = ? OR user2_id = ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

// Bind parameters dynamically
$types = str_repeat('i', count($conversation_ids)) . 'ii';
$params = array_merge($conversation_ids, [$user_id, $user_id]);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
