<?php
include '../../db_connect.php';
session_start();

// ✅ Ensure user is logged in
if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$user_id = $_SESSION['user']['id'];

// ✅ Pricing function based on regime and duration
function calculateSessionPrice($regime, $start_time, $end_time, $session_days) {
  // Philippine gym pricing - realistic rates
  $regimePrices = [
    'full_body' => 900,      // ₱900 per session
    'upper_body' => 750,     // ₱750 per session
    'lower_body' => 750,     // ₱750 per session
    'cardio' => 600,         // ₱600 per session
    'strength' => 850,       // ₱850 per session
    'flexibility' => 500,    // ₱500 per session
    'hiit' => 800,           // ₱800 per session
    'recovery' => 600        // ₱600 per session
  ];

  // Get base price for regime (default 750 if not found)
  $basePrice = $regimePrices[$regime] ?? 750;

  // Calculate duration in hours
  $start = new DateTime($start_time);
  $end = new DateTime($end_time);
  $duration = $start->diff($end);
  $hours = $duration->h + ($duration->i / 60);

  // Adjust price based on duration
  // 30 min session: 50% of base
  // 1 hour: 100% of base
  // 1.5 hours: 115% of base
  // 2 hours: 130% of base
  $durationMultiplier = $hours < 1 ? ($hours * 0.5 / 0.5) : (1 + ($hours - 1) * 0.3);

  // Calculate total for all session days
  $totalPrice = $basePrice * $durationMultiplier * intval($session_days);

  return round($totalPrice, 2);
}

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

  // ✅ Calculate price
  $amount = calculateSessionPrice($training_regime, $start_time, $end_time, $session_days);

  // ✅ Insert into database
  $query = "INSERT INTO appointments (
              trainee_id, trainer_id, date, start_time, end_time, 
              training_regime, notes, session_days, amount, status, is_paid, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 0, NOW())";

  $stmt = $conn->prepare($query);

  if (!$stmt) {
    die("Database error: " . $conn->error);
  }

  $stmt->bind_param(
    "iisssssid",
    $user_id,
    $trainer_id,
    $date,
    $start_time,
    $end_time,
    $training_regime,
    $notes,
    $session_days,
    $amount
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
