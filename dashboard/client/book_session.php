<?php
include '../../db_connect.php';
session_start();

// ✅ Ensure user is logged in
if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$user_id = $_SESSION['user']['id'];

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Collect and sanitize input values
  $trainer_id = isset($_POST['trainer_id']) ? intval($_POST['trainer_id']) : 0;
  $date = trim($_POST['date']);
  $start_time = trim($_POST['start_time']);
  $end_time = trim($_POST['end_time']);
  $training_regime = trim($_POST['training_regime']);
  $notes = trim($_POST['notes']);
  $session_days = trim($_POST['session_days']);

  // ✅ Basic input validation
  if (empty($trainer_id) || empty($date) || empty($start_time) || empty($end_time) || empty($training_regime)) {
    die("Error: All required fields must be filled out.");
  }

  // ✅ Insert into database
  $query = "INSERT INTO appointments (
              trainee_id, trainer_id, date, start_time, end_time, 
              training_regime, notes, session_days, status, is_paid, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 0, NOW())";

  $stmt = $conn->prepare($query);

  if (!$stmt) {
    die("Database error: " . $conn->error);
  }

  $stmt->bind_param(
    "iissssss",
    $user_id,
    $trainer_id,
    $date,
    $start_time,
    $end_time,
    $training_regime,
    $notes,
    $session_days
  );

  if ($stmt->execute()) {
    header("Location: client_schedule.php?msg=success");
    exit;
  } else {
    die("Error saving appointment: " . $stmt->error);
  }

  $stmt->close();
  $conn->close();
} else {
  // Redirect if not POST
  header("Location: client_schedule.php");
  exit;
}
?>
