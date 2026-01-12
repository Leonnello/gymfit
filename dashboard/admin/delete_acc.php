<?php
session_start();
include '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Only admin can delete users
if ($_SESSION['user']['role'] !== 'admin') {
     header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");
    exit;
}

$id = intval($_GET['id']);

// Prevent admin from deleting themselves
if ($id == $_SESSION['user']['id']) {
     header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");
    exit;
}

try {
    // Start transaction for data integrity
    $conn->begin_transaction();

    // 1. Delete related messages
    $deleteMessages = $conn->prepare("DELETE FROM messages WHERE sender_id = ?");
    $deleteMessages->bind_param("i", $id);
    $deleteMessages->execute();
    $deleteMessages->close();

    // 2. Delete related conversations
    $deleteConversations = $conn->prepare("DELETE FROM conversations WHERE user1_id = ? OR user2_id = ?");
    $deleteConversations->bind_param("ii", $id, $id);
    $deleteConversations->execute();
    $deleteConversations->close();

    // 3. Handle appointments - delete or update based on your business logic
    // Option A: Delete appointments (removes all appointment history)
    $deleteAppointments = $conn->prepare("DELETE FROM appointments WHERE trainer_id = ? OR trainee_id = ?");
    $deleteAppointments->bind_param("ii", $id, $id);
    $deleteAppointments->execute();
    $deleteAppointments->close();

    

    // 5. Delete user profile images if they exist
    $getUserFiles = $conn->prepare("SELECT avatar, idImage FROM users WHERE id = ?");
    $getUserFiles->bind_param("i", $id);
    $getUserFiles->execute();
    $userFiles = $getUserFiles->get_result()->fetch_assoc();
    $getUserFiles->close();

    // 6. Finally delete the user
    $deleteUser = $conn->prepare("DELETE FROM users WHERE id = ?");
    $deleteUser->bind_param("i", $id);
    $deleteUser->execute();

    if ($deleteUser->affected_rows > 0) {
        // Commit transaction if user was deleted
        $conn->commit();

        // Delete physical files after successful database deletion
        if ($userFiles) {
            $avatar = $userFiles['avatar'];
            $idImage = $userFiles['idImage'];

            $avatarPath = "../../uploads/avatar/" . $avatar;
            $idPath = "../../uploads/id/" . $idImage;

            if ($avatar && $avatar !== "default_avatar.png" && file_exists($avatarPath)) {
                unlink($avatarPath);
            }

            if ($idImage && $idImage !== "default_id.png" && file_exists($idPath)) {
                unlink($idPath);
            }
        }

         header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");

    } else {
        // Rollback if no user was deleted
        $conn->rollback();
        header("Location: /gymfit/dashboard/admin/users.php?error=unauthorized");
    
    }

    $deleteUser->close();

} catch (Exception $e) {
    // Rollback transaction on any error
    $conn->rollback();
    
    // Log the error for debugging
    error_log("Delete user error: " . $e->getMessage());
    
    header("Location: /gymfit/dashboard/admin/users.php?error=delete_failed&message=" . urlencode($e->getMessage()));
}

$conn->close();
exit;
?>