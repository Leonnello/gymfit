<?php
include '../../../db_connect.php';
session_start();

if (!isset($_SESSION['user'])) exit("unauthorized");

$user_id = $_SESSION['user']['id'];
$conversation_id = intval($_POST['conversation_id']);

$stmt = $conn->prepare("
    UPDATE messages 
    SET seen = 1 
    WHERE conversation_id = ? 
    AND sender_id != ?
");
$stmt->bind_param("ii", $conversation_id, $user_id);
$stmt->execute();

echo "ok";
?>
