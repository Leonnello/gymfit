<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $equipment_id = intval($_GET['id']);
    
    // Prepare the delete statement
    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $equipment_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Equipment deleted successfully";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error deleting equipment: " . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
    
    $stmt->close();
    $conn->close();
}

header("Location: inventory.php");
exit;
?>