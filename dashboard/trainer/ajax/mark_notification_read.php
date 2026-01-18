<?php
session_start();
require_once '../../../db_connect.php';

if (!isset($_SESSION['user']) || !isset($_POST['appointment_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$appointment_id = intval($_POST['appointment_id']);
$appointment_type = $_POST['type'] ?? 'session';

// Redirect based on type
$redirectUrl = ($appointment_type === 'chat') ? '../trainer_chat.php' : '../trainer_sessions.php';

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'redirect' => $redirectUrl
]);
