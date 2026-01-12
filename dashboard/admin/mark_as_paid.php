<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

if (!isset($_POST['id'])) {
  header("Location: admin_payments.php");
  exit;
}

$id = intval($_POST['id']);

$stmt = $conn->prepare("UPDATE appointments SET is_paid = 1 WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: admin_payments.php?msg=paid_success");
exit;
