<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    exit;
}

$user_id = $_SESSION['user']['id'];
$status = $_POST['status'] ?? 'offline';

$stmt = $conn->prepare("UPDATE users SET active_status=? WHERE id=?");
$stmt->bind_param("si", $status, $user_id);
$stmt->execute();
