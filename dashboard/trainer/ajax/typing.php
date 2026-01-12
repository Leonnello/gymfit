<?php
include '../../../db_connect.php';
session_start();

if (!isset($_SESSION['user'])) exit();

$user_id = $_SESSION['user']['id'];
$conversation_id = intval($_POST['conversation_id']);
$status = intval($_POST['is_typing']); // 1 or 0

$stmt = $conn->prepare("
    UPDATE conversations 
    SET typing_user = ?
    WHERE id = ?
");
$stmt->bind_param("ii", $user_id, $conversation_id);
$stmt->execute();

echo "ok";
?>
