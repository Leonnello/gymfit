<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

$role = $_SESSION['user']['role'];

// Redirect based on role
switch($role) {
    case 'admin':
        header("Location: admin/admin.php");
        break;
    case 'trainer':
    case 'trainor':
        header("Location: trainer/trainer.php");
        break;
    case 'client':
    case 'trainee':
        header("Location: client/client.php");
        break;
    case 'owner':
        header("Location: owner/owner.php");
        break;
    default:
        header("Location: ../login.php");
}
exit;
?>
