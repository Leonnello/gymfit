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

$check_status = $conn->query("SHOW COLUMNS FROM users LIKE 'active_status'");
if ($check_status->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD active_status ENUM('online','offline','busy') DEFAULT 'offline'");
}

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

$opposite_role = ($role === 'trainee') ? 'trainer' : 'trainee';

$conversations_result = $conn->query($conversations_query);
if (!$conversations_result) {
    die("Conversations Query Error: " . $conn->error);
}

$existing_ids = [];
$conversations_result->data_seek(0);
while ($conv = $conversations_result->fetch_assoc()) {
    $existing_ids[] = $conv['opposite_id'];
}

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
$users_stmt->bind_param("siii", $opposite_role, $user_id, $user_id, $user_id);
$users_stmt->execute();
$users_result = $users_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chat Support | GymFit</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
main { margin-top: 56px; margin-left: 260px; height: calc(100vh - 56px); display: flex; flex-direction: column; overflow: hidden; padding: 0; }
.chat-wrapper { display: flex; flex-direction: row; flex: 1; gap: 0; overflow: hidden; }
.left-sidebar { width: 30%; min-width: 250px; background: #fff; border-right: 1px solid #dee2e6; display: flex; flex-direction: column; overflow: hidden; }
.right-chatarea { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.chat-header { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #dee2e6; background: #fff; flex-shrink: 0; }
.chat-box { flex: 1; overflow-y: auto; padding: 15px; background: #fff; }
.chat-input { padding: 15px; border-top: 1px solid #dee2e6; background: #f8f9fa; flex-shrink: 0; }
.convo-list { flex: 1; overflow-y: auto; }
.convo-item { padding: 12px 15px; cursor: pointer; border-left: 4px solid transparent; border-bottom: 1px solid #f0f0f0; transition: all 0.2s; }
.convo-item:hover { background: #f8f9fa; border-left-color: #dc3545; }
.message { padding: 12px 16px; margin-bottom: 12px; border-radius: 18px; max-width: 70%; word-wrap: break-word; }
.sent { background: linear-gradient(135deg,#dc3545,#c82333); color: white; margin-left: auto; border-bottom-right-radius: 4px; }
.received { background: #f1f3f5; color: #333; border-bottom-left-radius: 4px; }
.toast-container { position: fixed; top: 80px; right: 20px; z-index: 2000; width: 350px; }
@media (max-width: 768px) {
    main { margin-left: 0; }
    .chat-wrapper { flex-direction: column; }
    .left-sidebar { width: 100%; height: 40%; min-width: unset; border-right: none; border-bottom: 1px solid #dee2e6; }
    .right-chatarea { height: 60%; }
    .message { max-width: 85%; }
    .chat-box { height: 300px; }
}
.dropdown-menu { z-index: 1050 !important; }
.navbar { z-index: 1049 !important; }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>
<div class="chat-wrapper">

<!-- LEFT SIDEBAR -->
<div class="left-sidebar">
<div style="padding: 15px; border-bottom: 1px solid #dee2e6; flex-shrink: 0;">
    <h6 class="text-danger mb-0"><i class="bi bi-people-fill me-2"></i>Conversations <span class="badge bg-danger ms-2" id="conversationCount"><?= $conversations_result->num_rows ?></span></h6>
</div>
<div class="convo-list">
<?php if ($conversations_result->num_rows > 0): ?>
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
        $statusColor = $conv['active_status'] === "online" ? "#28a745" : ($conv['active_status'] === "busy" ? "#ffc107" : "#6c757d");
    ?>
    <div class="convo-item" onclick="startConversation(<?= $conv['conversation_id'] ?>, <?= $conv['opposite_id'] ?>, '<?= addslashes($conv['opposite_name']) ?>', '<?= $avatarPath ?>', '<?= ucfirst($conv['opposite_role']) ?>')">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="position: relative;">
                <img src="<?= $avatarPath ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                <span style="position: absolute; bottom: 0; right: 2px; width: 12px; height: 12px; border-radius: 50%; background: <?= $statusColor ?>; border: 2px solid white;"></span>
            </div>
            <div style="flex: 1; min-width: 0;">
                <strong style="display: block;"><?= htmlspecialchars($conv['opposite_name']) ?></strong>
                <small class="text-muted" style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($conv['last_message'] ?? 'No messages') ?></small>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="text-center text-muted p-4">
        <i class="bi bi-chat-dots display-4"></i>
        <p class="mt-2">No conversations yet</p>
    </div>
<?php endif; ?>
</div>
<div style="padding: 15px; border-top: 1px solid #dee2e6; flex-shrink: 0;">
    <h6 class="text-danger mb-2"><i class="bi bi-person-plus me-2"></i>Start Chat</h6>
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
        <div class="d-flex align-items-center mb-2 p-2 bg-light rounded" style="cursor: pointer;">
            <img src="<?= $avatarPath ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;">
            <div style="flex: 1; min-width: 0;">
                <strong style="display: block;"><?= htmlspecialchars($u['name']) ?></strong>
                <small class="text-muted"><?= ucfirst($u['role']) ?></small>
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="startConversation(null, <?= $u['id'] ?>, '<?= addslashes($u['name']) ?>', '<?= $avatarPath ?>', '<?= ucfirst($u['role']) ?>')">
                <i class="bi bi-chat-left"></i>
            </button>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <small class="text-muted">No users available</small>
    <?php endif; ?>
</div>
</div>

<!-- RIGHT CHAT AREA -->
<div class="right-chatarea">
<div class="chat-header">
    <img id="chatAvatar" src="../../assets/default-avatar.png" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 15px;">
    <div style="flex: 1;">
        <h6 id="chatName" class="fw-bold m-0">Select a conversation</h6>
        <small id="chatRole" class="text-muted">Choose a trainer to start chatting</small>
        <div id="typingIndicator" style="font-style: italic; color: #6c757d; font-size: 0.9rem;"></div>
    </div>
</div>

<div id="chatBox" class="chat-box">
    <div class="text-center text-muted py-5">
        <i class="bi bi-chat-square-text display-4"></i>
        <p class="mt-3">Select a conversation to start messaging</p>
    </div>
</div>

<div class="chat-input">
<form id="sendMessageForm" class="d-flex gap-2">
    <input type="hidden" id="conversation_id">
    <input type="text" id="messageInput" class="form-control" placeholder="Type a message..." disabled>
    <button type="submit" class="btn btn-danger px-4" disabled>
        <i class="bi bi-send"></i> Send
    </button>
</form>
</div>
</div>

</div>
</main>

<script>
let conversationId = null;
let myUserId = <?= $user_id ?>;
let socket = null;
let useSocket = false;
let chatInputStorage = {};

function initializeSocket() {
    if (typeof io === 'undefined') {
        console.warn('Socket.IO not available');
        useSocket = false;
        return;
    }
    fetch("ajax/get_socket_auth.php")
        .then(res => res.json())
        .then(auth => {
            socket = io('http://localhost:3000', {
                auth: { user_id: auth.user_id, token: auth.token },
                reconnection: true,
                transports: ['websocket', 'polling']
            });
            socket.on('connect', () => {
                console.log('✅ Connected');
                useSocket = true;
            });
            socket.on('new_message', (data) => {
                if (data.conversation_id === conversationId) {
                    appendMessage(data);
                    scrollToBottom();
                    refreshConversationList();
                    showToast('New Message', `${data.sender_name || 'Someone'} sent you a message`);
                } else {
                    showToast('New Message', `New message in another chat`);
                    refreshConversationList();
                }
            });
        })
        .catch(err => console.log('Socket init failed'));
}

window.startConversation = function(convId, userId, name, avatar, role) {
    if (conversationId && document.getElementById("messageInput").value) {
        chatInputStorage[conversationId] = document.getElementById("messageInput").value;
    }
    document.getElementById("chatName").textContent = name;
    document.getElementById("chatRole").textContent = role;
    document.getElementById("chatAvatar").src = avatar;
    document.getElementById("messageInput").disabled = false;
    document.querySelector("#sendMessageForm button").disabled = false;
    
    if (convId) {
        conversationId = parseInt(convId);
        document.getElementById("conversation_id").value = conversationId;
        document.getElementById("chatBox").innerHTML = "";
        const savedInput = chatInputStorage[conversationId] || "";
        document.getElementById("messageInput").value = savedInput;
        loadMessages();
        if (socket) socket.emit('join_chat', { user_id: myUserId, conversation_id: conversationId });
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
        const savedInput = chatInputStorage[conversationId] || "";
        document.getElementById("messageInput").value = savedInput;
        loadMessages();
        if (socket) socket.emit('join_chat', { user_id: myUserId, conversation_id: conversationId });
    });
};

window.loadMessages = function() {
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
            data.forEach(msg => appendMessage(msg));
            scrollToBottom();
        });
};

function appendMessage(msg) {
    let box = document.getElementById("chatBox");
    if (box.innerHTML.includes("No messages yet")) {
        box.innerHTML = "";
    }
    let div = document.createElement("div");
    div.className = "message " + (msg.sender_id == myUserId ? "sent" : "received");
    div.innerHTML = `<div>${escapeHtml(msg.message)}</div><small style="font-size:0.7rem;opacity:0.7;">${formatTime(msg.created_at)}</small>`;
    box.appendChild(div);
}

function escapeHtml(text) {
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return text.replace(/[&<>"']/g, m => map[m]);
}

function scrollToBottom() {
    let box = document.getElementById("chatBox");
    if (box) setTimeout(() => { box.scrollTop = box.scrollHeight; }, 50);
}

function showToast(title, message) {
    let container = document.getElementById('toastContainer') || (() => {
        let c = document.createElement('div');
        c.id = 'toastContainer';
        c.className = 'toast-container';
        document.body.appendChild(c);
        return c;
    })();
    
    let toast = document.createElement('div');
    toast.className = 'alert alert-success alert-dismissible fade show';
    toast.style.cssText = 'margin-bottom:10px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);border-left:4px solid #28a745;';
    toast.innerHTML = `<div style="display:flex;align-items:start;gap:10px;"><div style="font-size:1.3rem;"><i class="bi bi-chat-left-text-fill text-success"></i></div><div style="flex:1;"><strong>${title}</strong><br><small>${message}</small></div><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    container.appendChild(toast);
    setTimeout(() => { if (toast.parentElement) toast.remove(); }, 5000);
}

function refreshConversationList() {
    fetch('ajax/get_conversations.php')
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                document.getElementById('conversationCount').textContent = data.length;
            }
        })
        .catch(err => console.log('Refresh failed'));
}

function formatTime(datetime) {
    const date = new Date(datetime.replace(" ", "T"));
    return date.toLocaleString([], { month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
}

document.getElementById("sendMessageForm").addEventListener("submit", function(e) {
    e.preventDefault();
    let input = document.getElementById("messageInput");
    let message = input.value.trim();
    if (!message || !conversationId) return;
    
    const tempMessage = { id: 'temp_' + Date.now(), conversation_id: conversationId, sender_id: myUserId, message: message, created_at: new Date().toISOString() };
    appendMessage(tempMessage);
    scrollToBottom();
    input.value = "";
    
    if (useSocket && socket && socket.connected) {
        socket.emit('send_message', { conversation_id: conversationId, sender_id: myUserId, message: message });
    } else {
        fetch("ajax/send_message.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: `conversation_id=${conversationId}&message=${encodeURIComponent(message)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) setTimeout(() => loadMessages(), 500);
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    initializeSocket();
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    setInterval(() => {
        if (!useSocket && conversationId) {
            loadMessages();
            refreshConversationList();
        }
    }, 2000);
});
</script>
</body>
</html>
