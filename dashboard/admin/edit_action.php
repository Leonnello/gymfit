<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];

    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $serial = $_POST['serial_number'];
    $category = $_POST['category_id'];
    $status = $_POST['status'];
    $purchase_date = $_POST['purchase_date'];
    $purchase_price = $_POST['purchase_price'];
    $last_maintenance = $_POST['last_maintenance'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE equipment SET 
        name=?, 
        brand=?, 
        model=?, 
        serial_number=?, 
        category_id=?, 
        status=?, 
        purchase_date=?, 
        purchase_price=?, 
        last_maintenance=?, 
        notes=? 
        WHERE id=?");

    $stmt->bind_param("ssssissdssi",
        $name,
        $brand,
        $model,
        $serial,
        $category,
        $status,
        $purchase_date,
        $purchase_price,
        $last_maintenance,
        $notes,
        $id
    );

    if ($stmt->execute()) {
        header("Location: inventory.php?updated=1");
        exit;
    } else {
        echo "Error updating record.";
    }

    $stmt->close();
}
?>
