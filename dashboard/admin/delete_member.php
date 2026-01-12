<?php 
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: members.php");
    exit;
}

$id = $_GET['id'];

// Fetch user details
$q = $conn->prepare("SELECT avatar, idImage, firstName, lastName FROM users WHERE id=?");
$q->bind_param("i", $id);
$q->execute();
$res = $q->get_result()->fetch_assoc();

$avatar = $res['avatar'];
$idImage = $res['idImage'];
$userName = $res['firstName'] . ' ' . $res['lastName'];

$avatarPath = "../../uploads/avatar/" . $avatar;
$idPath = "../../uploads/id/" . $idImage;

try {

    $conn->begin_transaction();

    // 1. Cancel appointments (trainee)
    $stmt1 = $conn->prepare("UPDATE appointments SET trainee_id = NULL, status = 'cancelled' WHERE trainee_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();

    // 2. Cancel appointments (trainer)
    $stmt2 = $conn->prepare("UPDATE appointments SET trainer_id = NULL, status = 'cancelled' WHERE trainer_id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();

    // 3. Archive the user
    $archive = $conn->prepare("
        INSERT INTO deleted_users_archive (original_id, firstName, lastName, email, role, deleted_at)
        SELECT id, firstName, lastName, email, role, NOW()
        FROM users WHERE id = ?
    ");
    $archive->bind_param("i", $id);
    $archive->execute();

    // 4. Delete messages (sender)
    $stmt3 = $conn->prepare("DELETE FROM messages WHERE sender_id = ?");
    $stmt3->bind_param("i", $id);
    $stmt3->execute();

    // 5. Delete conversations (user1 or user2)
    $stmt4 = $conn->prepare("DELETE FROM conversations WHERE user1_id = ? OR user2_id = ?");
    $stmt4->bind_param("ii", $id, $id);
    $stmt4->execute();

    // 6. Finally delete the user
    $stmt5 = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt5->bind_param("i", $id);
    $stmt5->execute();

    if ($stmt5->affected_rows === 0) {
        throw new Exception("User not deleted. MySQL blocked delete due to constraints.");
    }

    $conn->commit();

    // Remove files
    if ($avatar !== "default_avatar.png" && file_exists($avatarPath)) unlink($avatarPath);
    if ($idImage !== "default_id.png" && file_exists($idPath)) unlink($idPath);

    echo "<script>
            alert('Member \"$userName\" deleted successfully!');
            window.location.href='members.php';
          </script>";

} catch (Exception $e) {
    $conn->rollback();
    error_log("DELETE ERROR: " . $e->getMessage());
    echo "<script>
            alert('Delete failed. Check console. Error logged.');
            window.location.href='members.php';
          </script>";
}

// Close connections
$q->close();
$conn->close();
?>
