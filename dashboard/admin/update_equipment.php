<?php
session_start();
include '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Only allow admin (or staff, depending on your rules) to update
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: manage_equipment.php?error=unauthorized");
    exit;
}

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $serial_number = trim($_POST['serial_number']);
    $purchase_date = $_POST['purchase_date'];
    $purchase_price = floatval($_POST['purchase_price']);
    $status = $_POST['status'];
    $last_maintenance = $_POST['last_maintenance'] ?: NULL; // optional
    $notes = trim($_POST['notes']);

    // Prepare update statement
    $stmt = $conn->prepare("
        UPDATE equipment SET
            name = ?, 
            category_id = ?, 
            brand = ?, 
            model = ?, 
            serial_number = ?, 
            purchase_date = ?, 
            purchase_price = ?, 
            status = ?, 
            last_maintenance = ?, 
            notes = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "sissssdsssi",
        $name,
        $category_id,
        $brand,
        $model,
        $serial_number,
        $purchase_date,
        $purchase_price,
        $status,
        $last_maintenance,
        $notes,
        $id
    );

    if ($stmt->execute()) {
        header("Location: inventory.php?updated=true");
    } else {
        header("Location: inventory.php?error=update_failed");
    }
    exit;
} else {
    header("Location: inventory.php");
    exit;
}
?>
