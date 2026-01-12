<?php
include '../../db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $appointment_id = $_POST['appointment_id'];
  $amount = $_POST['amount'];

  $query = "UPDATE appointments SET is_paid = 1, amount = ? WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("di", $amount, $appointment_id);
  $stmt->execute();

  header("Location: client_schedule.php");
  exit;
}
?>
