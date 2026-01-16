<?php
// Set JSON header FIRST before anything else
header('Content-Type: application/json');

// Disable error display and catch all errors
error_reporting(E_ALL);
ini_set('display_errors', '0');

try {
    session_start();
    include '../../db_connect.php';

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
        echo json_encode(['error' => 'Missing required fields: regime=' . $training_regime . ', start=' . $start_time . ', end=' . $end_time]);
        exit;
    }

    // Philippine gym pricing - realistic rates per regime
    $regimePrices = [
        'full_body' => 150,
        'upper_body' => 120,
        'lower_body' => 120,
        'cardio' => 100,
        'strength' => 140,
        'flexibility' => 80,
        'hiit' => 130,
        'recovery' => 100
    ];

    // Get base price for regime (default 750 if not found)
    $basePrice = $regimePrices[$training_regime] ?? 750;

    // Calculate duration in hours with error handling
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $duration = $start->diff($end);
    $hours = $duration->h + ($duration->i / 60);
    
    if ($hours <= 0) {
        echo json_encode(['error' => 'End time must be after start time']);
        exit;
    }

    // Adjust price based on duration
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

    if (isset($conn)) {
        $conn->close();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
