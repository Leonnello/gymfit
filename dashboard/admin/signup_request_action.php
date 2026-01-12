<?php
include '../../db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'];

  if (isset($_POST['approve'])) {
    $status = 'Approved';
  } elseif (isset($_POST['decline'])) {
    $status = 'Declined';
  }

  $stmt = $conn->prepare("UPDATE signup_requests SET status = ? WHERE id = ?");
  $stmt->execute([$status, $id]);

  header("Location: signup_request_view.php");
  exit;
}
?>
