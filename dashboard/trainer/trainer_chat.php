<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = $user['role'];

// Ensure active_status exists
$check_status = $conn->query("SHOW COLUMNS FROM users LIKE 'active_status'");
if ($check_status->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD active_status ENUM('online','offline','busy') DEFAULT 'offline'");
}

// ===============================
// Conversations Query (FIXED)
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
       AND c.is_archived = 0
    ORDER BY c.last_message_at DESC, c.id DESC
";

$opposite_role = ($role === 'trainee') ? 'trainer' : 'trainee';

$conversations_result = $conn->query($conversations_query);
if (!$conversations_result) {
    die("Conversations Query Error: " . $conn->error);
}

// ===============================
// Collect existing IDs (FIXED)
// ===============================
$existing_ids = [];
$conversations_result->data_seek(0); // ✅ REQUIRED
while ($conv = $conversations_result->fetch_assoc()) {
    $existing_ids[] = $conv['opposite_id'];
}

// ===============================
// Fetch users not yet in conversation
// ===============================
$users_query = "
    SELECT 
        u.id,
        CONCAT(u.firstName,' ',u.lastName) AS name,
        u.avatar,
        u.role,
        u.active_status
    FROM users u
    WHERE u.role = ?
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
if (!$users_stmt) {
    die("Users Prepare Error: " . $conn->error);
}

$users_stmt->bind_param("siii", $opposite_role, $user_id, $user_id, $user_id);
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
body { background:#f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
main { margin-top:56px; margin-left:260px; height:calc(100vh - 56px); overflow-y:auto; padding:20px; }
.chat-container { height:calc(100vh - 150px); }
.chat-box { height:calc(100% - 140px); overflow-y:auto; background:#fff; padding:15px; border-radius:0 0 8px 8px; }
.message { padding:12px 16px; border-radius:18px; margin-bottom:12px; max-width:70%; word-wrap:break-word; }
.sent { background:linear-gradient(135deg,#dc3545,#c82333); color:white; margin-left:auto; border-bottom-right-radius:4px; }
.received { background:#f1f3f5; color:#333; border-bottom-left-radius:4px; }
.conversation-item { padding:12px; cursor:pointer; border-radius:8px; border-left:3px solid transparent; }
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

<!-- LEFT SIDEBAR -->
<div class="col-md-4">
<div class="card shadow-sm h-100">
<div class="card-header bg-danger text-white d-flex justify-content-between">
    <h6 class="mb-0"><i class="bi bi-people-fill me-2"></i>Your Conversations</h6>
    <span class="badge bg-light text-danger" id="conversationCount"><?= $conversations_result->num_rows ?></span>
</div>

<div class="card-body p-0">
<div class="conversation-list">
<?php if ($conversations_result->num_rows > 0): ?>
<ul class="list-group list-group-flush">
<?php 
$conversations_result->data_seek(0);
while ($conv = $conversations_result->fetch_assoc()): 
$avatarPath = "../../assets/default-avatar.png";
if (!empty($conv['avatar'])) {
    $path1 = "../../uploads/avatars/" . $conv['avatar'];
    $path2 = "../../uploads/" . $conv['avatar'];
    if (file_exists($path1)) $avatarPath = $path1;
    elseif (file_exists($path2)) $avatarPath = $path2;
}
$statusColor = $conv['active_status'] === "online" ? "#28a745" :
               ($conv['active_status'] === "busy" ? "#ffc107" : "#6c757d");
$other_id = $conv['opposite_id'];
?>
<li class="list-group-item conversation-item"
    onclick="startConversation(<?= $conv['conversation_id'] ?>, <?= $other_id ?>, '<?= addslashes($conv['opposite_name']) ?>', '<?= $avatarPath ?>', '<?= ucfirst($conv['opposite_role']) ?>')">
    <div class="d-flex align-items-center">
        <div class="position-relative me-3">
            <img src="<?= $avatarPath ?>" class="trainer-avatar">
            <span style="position:absolute;bottom:0;right:3px;width:12px;height:12px;border-radius:50%;
            background:<?= $statusColor ?>;border:2px solid white;"></span>
        </div>
        <div class="flex-grow-1">
            <strong class="d-block"><?= htmlspecialchars($conv['opposite_name']) ?></strong>
            <small class="text-muted"><?= ucfirst($conv['opposite_role']) ?></small>
            <small class="text-muted d-block text-truncate" style="max-width:180px;font-size:0.8rem;">
                <?= htmlspecialchars($conv['last_message'] ?? '') ?>
            </small>
        </div>
    </div>
    <button class="btn btn-sm btn-outline-secondary" onclick="archiveConversation(event, <?= $conv['conversation_id'] ?>)">
        <i class="bi bi-archive"></i>
    </button>
</li>

<?php endwhile; ?>
</ul>
<?php else: ?>
<div class="text-center text-muted p-4">
    <i class="bi bi-chat-dots display-4"></i>
    <p class="mt-2">No conversations yet</p>
</div>
<?php endif; ?>
</div>
</div>

<!-- New Chat Section -->
<div class="card-footer bg-light">
<h6 class="text-danger mb-3">
    <i class="bi bi-person-plus me-2"></i>Start New Chat
</h6>

<?php if ($users_result->num_rows > 0): ?>
    <?php while ($u = $users_result->fetch_assoc()): 
        $avatarPath = "../../default-avatar.png";
        if (!empty($u['avatar'])) {
            $path1 = "../../uploads/avatars/" . $u['avatar'];
            $path2 = "../../uploads/" . $u['avatar'];
            if (file_exists($path1)) $avatarPath = $path1;
            elseif (file_exists($path2)) $avatarPath = $path2;
        }
    ?>
    <div class="d-flex align-items-center mb-3 p-2 bg-white rounded">
        <div class="position-relative me-3">
            <img src="<?= $avatarPath ?>" width="40" height="40" class="rounded-circle">
            <span style="position:absolute;bottom:0;right:2px;width:10px;height:10px;border-radius:50%;
                background:<?= $u['active_status']=='online' ? '#28a745' : ($u['active_status']=='busy' ? '#ffc107' : '#6c757d') ?>;
                border:2px solid white;">
            </span>
        </div>
        <div class="flex-grow-1">
            <strong><?= htmlspecialchars($u['name']) ?></strong>
            <small class="text-muted d-block"><?= ucfirst($u['role']) ?></small>
        </div>
        <button class="btn btn-sm btn-outline-danger"
            onclick="startConversation(null, <?= $u['id'] ?>, '<?= addslashes($u['name']) ?>', '<?= $avatarPath ?>', '<?= ucfirst($u['role']) ?>')">
            <i class="bi bi-chat-left"></i>
        </button>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="text-center text-muted py-3">
        <small>No new users available</small>
    </div>
<?php endif; ?>
</div>
</div>
</div>

<!-- RIGHT SIDE – Chat Box -->
<div class="col-md-8">
<div class="card shadow-sm h-100">

<div class="chat-header d-flex align-items-center p-3">
    <img id="chatAvatar" src="../../assets/default-avatar.png" width="50" height="50" class="rounded-circle me-3">
    <div class="flex-grow-1">
        <h6 id="chatName" class="fw-bold m-0">Select a conversation</h6>
        <small id="chatRole" class="text-muted">Choose a user to start chatting</small>
        <div id="typingIndicator" class="typing-indicator"></div>
    </div>
    <div id="chatActions" class="d-none">
        <button class="btn btn-sm btn-outline-danger" onclick="clearChat()">
            <i class="bi bi-trash"></i> Clear
        </button>
    </div>
</div>

<div id="chatBox" class="chat-box">
    <div class="text-center text-muted py-5">
        <i class="bi bi-chat-square-text display-4"></i>
        <p class="mt-3">Select a conversation to start messaging</p>
    </div>
</div>

<div class="card-footer bg-light">
<form id="sendMessageForm" class="d-flex gap-2">
    <input type="hidden" id="conversation_id" name="conversation_id">
    <input type="text" id="messageInput" name="message" class="form-control" placeholder="Type a message..." disabled required>
    <button type="submit" class="btn btn-danger px-4" disabled>
        <i class="bi bi-send"></i> Send
    </button>
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

/* =========================
   START CONVERSATION
========================= */
window.startConversation = function (convId, userId, name, avatar, role) {

    document.getElementById("chatName").textContent = name;
    document.getElementById("chatRole").textContent = role;
    document.getElementById("chatAvatar").src = avatar;
    document.getElementById("messageInput").disabled = false;
    document.querySelector("#sendMessageForm button").disabled = false;

    if (convId) {
        conversationId = parseInt(convId);
        document.getElementById("conversation_id").value = conversationId;
        document.getElementById("chatBox").innerHTML = "";
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
        conversationId = parseInt(data.conversation_id);
        document.getElementById("conversation_id").value = conversationId;
        document.getElementById("chatBox").innerHTML = "";
        loadMessages();
        addConversationToList(conversationId, userId, name, avatar, role);
    });
};

/* =========================
   LOAD MESSAGES
========================= */
window.loadMessages = function () {
    if (!conversationId) return;

    fetch("ajax/get_messages.php?conversation_id=" + conversationId)
        .then(res => res.json())
        .then(data => {
            let box = document.getElementById("chatBox");
            box.innerHTML = "";

            if (data.length === 0) {
                box.innerHTML = `<div class="text-center text-muted py-5">No messages yet</div>`;
                return;
            }

            data.forEach(msg => {
                let div = document.createElement("div");
                div.className = "message " + (msg.sender_id == myUserId ? "sent" : "received");
                div.innerHTML = `
    <div>${msg.message}</div>
    <small style="font-size:0.7rem;opacity:0.7;">
        ${formatTime(msg.created_at)}
    </small>
`;

                box.appendChild(div);
            });

            box.scrollTop = box.scrollHeight;
        });
};

/* =========================
   SEND MESSAGE
========================= */
document.getElementById("sendMessageForm").addEventListener("submit", function (e) {
    e.preventDefault();

    let input = document.getElementById("messageInput");
    let message = input.value.trim();

    if (!message || !conversationId) return;

    fetch("ajax/send_message.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: `conversation_id=${conversationId}&message=${encodeURIComponent(message)}`
    }).then(() => {
        input.value = "";
        loadMessages();
    });
});

/* =========================
   AUTO REFRESH
========================= */
setInterval(loadMessages, 1500);

/* =========================
   ADD CONVERSATION
========================= */
function addConversationToList(convId, userId, name, avatar, role) {
    let list = document.querySelector(".conversation-list ul");
    if (!list) {
        list = document.createElement("ul");
        list.className = "list-group list-group-flush";
        document.querySelector(".conversation-list").appendChild(list);
    }

    const li = document.createElement("li");
    li.className = "list-group-item conversation-item active";
    li.onclick = () => startConversation(convId, userId, name, avatar, role);

    li.innerHTML = `
        <div class="d-flex align-items-center">
            <img src="${avatar}" class="trainer-avatar me-3">
            <div>
                <strong>${name}</strong><br>
                <small class="text-muted">${role}</small>
            </div>
        </div>
    `;

    document.querySelectorAll(".conversation-item").forEach(el => el.classList.remove("active"));
    list.prepend(li);
}
function formatTime(datetime) {
    const date = new Date(datetime.replace(" ", "T"));
    return date.toLocaleString([], {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

</script>


</body>
</html>
 