<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'];
    $first = $_POST['firstName'];
    $middle = $_POST['middleName'];
    $last = $_POST['lastName'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Fetch existing data first
    $query = $conn->prepare("SELECT avatar, idImage FROM users WHERE id=?");
    $query->bind_param("i", $id);
    $query->execute();
    $old = $query->get_result()->fetch_assoc();
    $oldAvatar = $old['avatar'];
    $oldIdImage = $old['idImage'];

    // Upload directory
    $avatarUploadDir = "../../uploads/avatar/";
    $idUploadDir = "../../uploads/id/";

    // ------------------------
    // Avatar Upload Handling
    // ------------------------
    if (!empty($_FILES['avatar']['name'])) {
        $avatarName = time() . "_" . basename($_FILES['avatar']['name']);
        $targetAvatar = $avatarUploadDir . $avatarName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetAvatar)) {
            $avatarFinal = $avatarName;

            // delete old avatar if not default
            if ($oldAvatar !== "default_avatar.png" && file_exists($avatarUploadDir . $oldAvatar)) {
                unlink($avatarUploadDir . $oldAvatar);
            }

        } else {
            $avatarFinal = $oldAvatar;
        }
    } else {
        $avatarFinal = $oldAvatar;
    }

    // ------------------------
    // ID Image Upload Handling
    // ------------------------
    if (!empty($_FILES['idImage']['name'])) {
        $idName = time() . "_" . basename($_FILES['idImage']['name']);
        $targetID = $idUploadDir . $idName;

        if (move_uploaded_file($_FILES['idImage']['tmp_name'], $targetID)) {
            $idFinal = $idName;

            // delete old id image if not default
            if ($oldIdImage !== "default_id.png" && file_exists($idUploadDir . $oldIdImage)) {
                unlink($idUploadDir . $oldIdImage);
            }

        } else {
            $idFinal = $oldIdImage;
        }
    } else {
        $idFinal = $oldIdImage;
    }

    // UPDATE QUERY
    $stmt = $conn->prepare("UPDATE users SET 
        firstName=?, 
        middleName=?, 
        lastName=?, 
        username=?, 
        email=?, 
        contact=?, 
        role=?, 
        status=?, 
        avatar=?, 
        idImage=? 
        WHERE id=?");

    $stmt->bind_param("ssssssssssi",
        $first,
        $middle,
        $last,
        $username,
        $email,
        $contact,
        $role,
        $status,
        $avatarFinal,
        $idFinal,
        $id
    );

    if ($stmt->execute()) {
        echo "<script>
                alert('Member updated successfully!');
                window.location.href='members.php';
              </script>";
    } else {
        echo "<script>
                alert('Update failed.');
                window.location.href='members.php';
              </script>";
    }
}
?>
