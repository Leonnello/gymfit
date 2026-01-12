<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int)$user['id'];
$role = $user['role']; // trainee (client)

// ===============================
// Ensure active_status exists
// ===============================
$check_status = $conn->query("SHOW COLUMNS FROM users LIKE 'active_status'");
if ($check_status->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD active_status ENUM('online','offline','busy') DEFAULT 'offline'");
}

// ===============================
// CLIENT → TRAINER conversations
// ===============================
$conversations_query = "
    SELECT 
        c.id AS conversation_id,
        u.id AS opposite_id,
        CONCAT(u.firstName,' ',u.lastName) AS opposite_name,
        u.avatar,
        u.role AS opposite_role,
        u.active_status,
        c.last_message,
        c.last_message_at
    FROM conversations c
    JOIN users u 
      ON u.id = IF(c.user1_id = $user_id, c.user2_id, c.user1_id)
    WHERE c.user1_id = $user_id 
       OR c.user2_id = $user_id
    ORDER BY c.last_message_at DESC, c.id DESC
";

$conversations_result = $conn->query($conversations_query);
if (!$conversations_result) {
    die("Conversations Query Error: " . $conn->error);
}

// ===============================
// Fetch trainers not yet chatted
// ===============================
$users_query = "
    SELECT 
        u.id,
        CONCAT(u.firstName,' ',u.lastName) AS name,
        u.avatar,
        u.role,
        u.active_status
    FROM users u
    WHERE u.role = 'trainer'
      AND u.id != ?
      AND NOT EXISTS (
          SELECT 1
          FROM conversations c
          WHERE 
            (c.user1_id = ? AND c.user2_id = u.id)
            OR
            (c.user2_id = ? AND c.user1_id = u.id)
      )
";

$users_stmt = $conn->prepare($users_query);
$users_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$users_stmt->execute();
$users_result = $users_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat Support | GymFit</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* 🔒 DESIGN UNCHANGED */
body { background:#f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
main { margin-top:56px; margin-left:260px; height:calc(100vh - 56px); overflow-y:auto; padding:20px; }
.chat-container { height:calc(100vh - 150px); }
.chat-box { height:calc(100% - 140px); overflow-y:auto; background:#fff; padding:15px; border-radius:0 0 8px 8px; }
.message { padding:12px 16px; border-radius:18px; margin-bottom:12px; max-width:70%; word-wrap:break-word; }
.sent { background:linear-gradient(135deg,#dc3545,#c82333); color:white; margin-left:auto; border-bottom-right-radius:4px; }
.received { background:#f1f3f5; color:#333; border-bottom-left-radius:4px; }
.conversation-item { padding:12px; cursor:pointer; border-radius:8px; border-left:3px solid transparent; display:flex; align-items:center; }
.conversation-item:hover, .conversation-item.active { background:#fff5f5; border-left-color:#dc3545; }
.trainer-avatar { width:45px; height:45px; border-radius:50%; object-fit:cover; }
.conversation-list { height:400px; overflow-y:auto; }
.typing-indicator { font-style:italic; color:#6c757d; font-size:0.9rem; }
</style>
</head>

<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>
<div class="container-fluid">

<h3 class="fw-bold mb-4 text-danger">
    <i class="bi bi-chat-dots me-2"></i>Chat Support
</h3>

<div class="row chat-container">

<!-- LEFT -->
<div class="col-md-4">
<div class="card shadow-sm h-100">

<div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
    <h6 class="mb-0">
        <i class="bi bi-people-fill me-2"></i>Your Conversations
        <span class="badge bg-light text-danger ms-2"><?= $conversations_result->num_rows ?></span>
    </h6>
</div>

<div class="card-body p-0">
<div class="conversation-list">
<ul class="list-group list-group-flush">
<?php while ($conv = $conversations_result->fetch_assoc()):
    $avatar = "../../assets/default-avatar.png";
    if (!empty($conv['avatar'])) {
        if (file_exists("../../uploads/avatars/".$conv['avatar'])) {
            $avatar = "../../uploads/avatars/".$conv['avatar'];
        }
    }
?>
<li class="list-group-item conversation-item"
    onclick="startConversation(<?= $conv['conversation_id'] ?>, <?= $conv['opposite_id'] ?>, '<?= addslashes($conv['opposite_name']) ?>', '<?= $avatar ?>', 'Trainer')">
    <img src="<?= $avatar ?>" class="trainer-avatar me-3">
    <div>
        <strong><?= htmlspecialchars($conv['opposite_name']) ?></strong>
        <small class="text-muted d-block">Trainer</small>
        <small class="text-muted"><?= htmlspecialchars($conv['last_message'] ?? '') ?></small>
    </div>
</li>
<?php endwhile; ?>
</ul>
</div>
</div>

<!-- Start New -->
<div class="card-footer bg-light">
<h6 class="text-danger mb-3"><i class="bi bi-person-plus me-2"></i>Start New Chat</h6>

<?php while ($u = $users_result->fetch_assoc()):
    $avatar = "../../assets/default-avatar.png";
    if (!empty($u['avatar']) && file_exists("../../uploads/avatars/".$u['avatar'])) {
        $avatar = "../../uploads/avatars/".$u['avatar'];
    }
?>
<div class="d-flex align-items-center mb-3 p-2 bg-white rounded">
    <img src="<?= $avatar ?>" width="40" height="40" class="rounded-circle me-3">
    <div class="flex-grow-1">
        <strong><?= htmlspecialchars($u['name']) ?></strong>
        <small class="text-muted d-block">Trainer</small>
    </div>
    <button class="btn btn-sm btn-outline-danger"
        onclick="startConversation(null, <?= $u['id'] ?>, '<?= addslashes($u['name']) ?>', '<?= $avatar ?>', 'Trainer')">
        <i class="bi bi-chat-left"></i>
    </button>
</div>
<?php endwhile; ?>

</div>
</div>
</div>

<!-- RIGHT -->
<div class="col-md-8">
<div class="card shadow-sm h-100">

<div class="chat-header d-flex align-items-center p-3">
    <img id="chatAvatar" src="../../assets/default-avatar.png" width="50" height="50" class="rounded-circle me-3">
    <div>
        <h6 id="chatName" class="fw-bold m-0">Select a conversation</h6>
        <small id="chatRole" class="text-muted">Trainer</small>
    </div>
</div>

<div id="chatBox" class="chat-box text-center text-muted py-5">
    Select a conversation to start messaging
</div>

<div class="card-footer bg-light">
<form id="sendMessageForm" class="d-flex gap-2">
    <input type="hidden" id="conversation_id">
    <input type="text" id="messageInput" class="form-control" disabled required>
    <button class="btn btn-danger" disabled><i class="bi bi-send"></i></button>
</form>
</div>

</div>
</div>

</div>
</div>
</main>

<script>
let conversationId = null;
let myUserId = <?= $user_id ?>;

function startConversation(convId, userId, name, avatar) {
    document.getElementById("chatName").textContent = name;
    document.getElementById("chatAvatar").src = avatar;
    document.getElementById("messageInput").disabled = false;
    document.querySelector("#sendMessageForm button").disabled = false;

    if (convId) {
        conversationId = convId;
        loadMessages();
        return;
    }

    fetch("ajax/start_conversation.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: `user2_id=${userId}`
    })
    .then(res => res.json())
    .then(data => {
        conversationId = data.conversation_id;
        loadMessages();
        location.reload();
    });
}

function loadMessages() {
    if (!conversationId) return;
    fetch("ajax/get_messages.php?conversation_id=" + conversationId)
        .then(res => res.json())
        .then(data => {
            const box = document.getElementById("chatBox");
            box.innerHTML = "";
            data.forEach(m => {
                const div = document.createElement("div");
                div.className = "message " + (m.sender_id == myUserId ? "sent" : "received");
                div.innerHTML = m.message;
                box.appendChild(div);
            });
            box.scrollTop = box.scrollHeight;
        });
}

document.getElementById("sendMessageForm").addEventListener("submit", e => {
    e.preventDefault();
    const msg = messageInput.value.trim();
    if (!msg) return;
    fetch("ajax/send_message.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: `conversation_id=${conversationId}&message=${encodeURIComponent(msg)}`
    }).then(() => {
        messageInput.value = "";
        loadMessages();
    });
});

setInterval(loadMessages, 1500);
</script>

</body>
</html>
