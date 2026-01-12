<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $brand = $conn->real_escape_string($_POST['brand']);
    $model = $conn->real_escape_string($_POST['model']);
    $serial_number = $conn->real_escape_string($_POST['serial_number']);
    $purchase_date = $_POST['purchase_date'];
    $purchase_price = floatval($_POST['purchase_price']);
    $status = $conn->real_escape_string($_POST['status']);
    $last_maintenance = $_POST['last_maintenance'] ?: NULL;
    $notes = $conn->real_escape_string($_POST['notes']);

    $stmt = $conn->prepare("INSERT INTO equipment 
        (name, category_id, brand, model, serial_number, purchase_date, purchase_price, status, last_maintenance, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssdsss", $name, $category_id, $brand, $model, $serial_number, $purchase_date, $purchase_price, $status, $last_maintenance, $notes);

    if ($stmt->execute()) {
        echo "<script>alert('Equipment added successfully'); window.location='inventory.php';</script>";
    } else {
        echo "<script>alert('Failed to add equipment'); window.location='inventory.php';</script>";
    }
}
