<?php
include '../../db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$id = $_GET['id'];
$conn->query("UPDATE appointments SET status='cancelled' WHERE id='$id'");
header("Location: client_schedule.php");
exit;
?>
