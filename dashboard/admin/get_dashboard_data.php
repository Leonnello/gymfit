<?php
include '../../db_connect.php';

// Default response
$response = [
  'signups' => 0,
  'members' => 0,
  'staff' => 0,
  'payments' => 0,
  'inventory' => 0,
  'analytics' => 0
];

try {
  // Sign-up Requests
  $signupQuery = $conn->query("SELECT COUNT(*) AS count FROM signup_requests");
  $response['signups'] = $signupQuery->fetch_assoc()['count'] ?? 0;

  // Members
  $membersQuery = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'member'");
  $response['members'] = $membersQuery->fetch_assoc()['count'] ?? 0;

  // Staff
  $staffQuery = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role IN ('trainer', 'staff')");
  $response['staff'] = $staffQuery->fetch_assoc()['count'] ?? 0;

  // Payments
  $paymentsQuery = $conn->query("SELECT COUNT(*) AS count FROM appointments WHERE isPaid = 1");
  $response['payments'] = $paymentsQuery->fetch_assoc()['count'] ?? 0;

  // Inventory
  $inventoryQuery = $conn->query("SELECT COUNT(*) AS count FROM inventory");
  $response['inventory'] = $inventoryQuery->fetch_assoc()['count'] ?? 0;

  // Analytics (custom metric: total combined)
  $response['analytics'] = $response['signups'] + $response['members'] + $response['staff'] + $response['inventory'];

  header('Content-Type: application/json');
  echo json_encode($response);
} catch (Exception $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
