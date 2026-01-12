<?php
session_start();
include "../../../db_connect.php";

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user']['id'];
$other_user_id = intval($_POST['user2_id']);
$conversation_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
$message = trim($_POST['message'] ?? '');

if ($conversation_id <= 0 || $message === '') {
    echo json_encode([
        "success" => false,
        "error" => "Invalid data"
    ]);
    exit;
}

/* ✅ INSERT MESSAGE */
$sql = "INSERT INTO messages (conversation_id, sender_id, message, created_at)
        VALUES (?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $conversation_id, $user_id, $message);

if ($stmt->execute()) {

    /* ✅ UPDATE CONVERSATION PREVIEW */
    $update = $conn->prepare("
        UPDATE conversations 
        SET last_message = ?, last_message_at = NOW()
        WHERE id = ?
    ");
    $update->bind_param("si", $message, $conversation_id);
    $update->execute();

    echo json_encode([
        "success" => true,
        "sender_id" => $user_id
    ]);

} else {
    echo json_encode([
        "success" => false,
        "error" => $stmt->error
    ]);
}