<?php
session_start();
include '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Only admin can delete users
if ($_SESSION['user']['role'] !== 'admin') {
     header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");
    exit;
}

$id = intval($_GET['id']);

// Prevent admin from deleting themselves
if ($id == $_SESSION['user']['id']) {
    header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");
    exit;
}

// Delete user securely
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");
   
} else {
     header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");
   
}
exit;
?>
