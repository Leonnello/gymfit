<?php
/**
 * Calculate booking price based on regime, duration, and number of days
 * Philippine gym pricing - realistic rates
 */
session_start();
include '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$training_regime = isset($_POST['training_regime']) ? $_POST['training_regime'] : '';
$start_time = isset($_POST['start_time']) ? $_POST['start_time'] : '';
$end_time = isset($_POST['end_time']) ? $_POST['end_time'] : '';
$session_days = isset($_POST['session_days']) ? intval($_POST['session_days']) : 1;

if (!$training_regime || !$start_time || !$end_time) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Philippine gym pricing - realistic rates per regime
$regimePrices = [
    'full_body' => 900,      // ₱900 per session - Complete workout
    'upper_body' => 750,     // ₱750 per session - Arms, chest, back, shoulders
    'lower_body' => 750,     // ₱750 per session - Legs, glutes, core
    'cardio' => 600,         // ₱600 per session - Running, cycling, elliptical
    'strength' => 850,       // ₱850 per session - Powerlifting, heavy weights
    'flexibility' => 500,    // ₱500 per session - Yoga, stretching
    'hiit' => 800,           // ₱800 per session - High intensity interval training
    'recovery' => 600        // ₱600 per session - Light exercise, recovery
];

// Get base price for regime (default 750 if not found)
$basePrice = $regimePrices[$training_regime] ?? 750;

// Calculate duration in hours with error handling
try {
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $duration = $start->diff($end);
    $hours = $duration->h + ($duration->i / 60);
    
    if ($hours <= 0) {
        echo json_encode(['error' => 'End time must be after start time']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Invalid time format: ' . $e->getMessage()]);
    exit;
}

// Adjust price based on duration
// 30 min: 50% of base
// 1 hour: 100% of base
// 1.5 hours: 115% of base
// 2 hours: 130% of base
$durationMultiplier = $hours < 1 ? ($hours * 0.5 / 0.5) : (1 + ($hours - 1) * 0.3);

// Calculate price per session
$pricePerSession = $basePrice * $durationMultiplier;

// Calculate total for all session days
$totalPrice = $pricePerSession * $session_days;

echo json_encode([
    'success' => true,
    'base_price' => $basePrice,
    'regime' => $training_regime,
    'duration' => round($hours, 2),
    'duration_multiplier' => round($durationMultiplier, 2),
    'price_per_session' => round($pricePerSession, 2),
    'session_days' => $session_days,
    'total_price' => round($totalPrice, 2),
    'formatted_price' => '₱' . number_format($totalPrice, 2),
    'breakdown' => '₱' . number_format($basePrice, 2) . ' × ' . round($durationMultiplier, 2) . ' × ' . $session_days . ' days'
]);
    'breakdown' => '₱' . number_format($basePrice, 2) . ' × ' . $durationMultiplier . ' × ' . $session_days . ' days'
]);

$conn->close();
?>
