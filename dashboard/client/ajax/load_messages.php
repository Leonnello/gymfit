<?php
session_start();
include '../../../db_connect.php';

$user_id = intval($_SESSION['user']['id'] ?? 0);
$conversation_id = intval($_GET['conversation_id'] ?? 0);

if (!$user_id || !$conversation_id) {
    echo '<p class="text-center text-danger mt-3">Invalid conversation.</p>';
    exit;
}

// Fetch messages
$sql = "SELECT m.*, u.firstName, u.lastName, u.avatar 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.conversation_id = ? 
        ORDER BY m.created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<p class="text-center text-muted mt-3">No messages yet. Start the conversation!</p>';
    exit;
}

while ($msg = $result->fetch_assoc()) {
    $isSent = ($msg['sender_id'] == $user_id);
    $avatar = !empty($msg['avatar']) && file_exists("../../uploads/avatars/".$msg['avatar']) 
        ? "../../uploads/avatars/".$msg['avatar'] 
        : "../../assets/default-avatar.png";
    $time = date("h:i A", strtotime($msg['created_at']));
    ?>
    <div class="d-flex mb-3 <?= $isSent ? 'justify-content-end' : 'justify-content-start' ?>">
        <?php if (!$isSent): ?>
            <img src="<?= $avatar ?>" class="rounded-circle me-2" width="40" height="40">
        <?php endif; ?>
        <div class="message <?= $isSent ? 'sent' : 'received' ?>">
            <?= htmlspecialchars($msg['message']) ?>
            <div class="text-end text-muted small mt-1"><?= $time ?></div>
        </div>
        <?php if ($isSent): ?>
            <img src="<?= $avatar ?>" class="rounded-circle ms-2" width="40" height="40">
        <?php endif; ?>
    </div>
<?php
}
?>
