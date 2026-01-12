<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $firstName = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $lastName = $_POST['lastName'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Handle Avatar Upload
    $avatarPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarTmp = $_FILES['avatar']['tmp_name'];
        $avatarName = time() . '_' . basename($_FILES['avatar']['name']);
        $avatarDir = '../../uploads/avatars/';
        if (!is_dir($avatarDir)) mkdir($avatarDir, 0777, true);
        move_uploaded_file($avatarTmp, $avatarDir . $avatarName);
        $avatarPath = 'uploads/avatars/' . $avatarName;
    }

    // Handle ID Image Upload
    $idImagePath = null;
    if (isset($_FILES['idImage']) && $_FILES['idImage']['error'] === UPLOAD_ERR_OK) {
        $idTmp = $_FILES['idImage']['tmp_name'];
        $idName = time() . '_id_' . basename($_FILES['idImage']['name']);
        $idDir = '../../uploads/idImages/';
        if (!is_dir($idDir)) mkdir($idDir, 0777, true);
        move_uploaded_file($idTmp, $idDir . $idName);
        $idImagePath = 'uploads/idImages/' . $idName;
    }

    // Fetch current avatar and idImage to keep if no new upload
    $stmtSelect = $conn->prepare("SELECT avatar, idImage FROM users WHERE id = ?");
    $stmtSelect->bind_param("i", $id);
    $stmtSelect->execute();
    $result = $stmtSelect->get_result()->fetch_assoc();

    if (!$avatarPath) $avatarPath = $result['avatar'];
    if (!$idImagePath) $idImagePath = $result['idImage'];

    // Update user
    $stmt = $conn->prepare("UPDATE users SET firstName=?, middleName=?, lastName=?, username=?, email=?, contact=?, role=?, status=?, avatar=?, idImage=? WHERE id=?");
    $stmt->bind_param("ssssssssssi", $firstName, $middleName, $lastName, $username, $email, $contact, $role, $status, $avatarPath, $idImagePath, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "User updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update user: " . $stmt->error;
    }

    header("Location: manage_users.php");
    exit;
}
?>
