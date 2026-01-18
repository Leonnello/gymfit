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
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>

<style>
body { background:#f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
main { margin-top:56px; margin-left:260px; height:calc(100vh - 56px); overflow-y:auto; padding:20px; }
.chat-container { display:flex; flex-direction:row; height:calc(100vh - 200px); gap: 15px; }
.chat-box { flex:1; height:calc(100vh - 280px); overflow-y:auto !important; background:#fff; padding:15px; border-radius:0 0 8px 8px; border: 1px solid #dee2e6; }
.message { padding:12px 16px; border-radius:18px; margin-bottom:12px; max-width:70%; word-wrap:break-word; }
.sent { background:linear-gradient(135deg,#dc3545,#c82333); color:white; margin-left:auto; border-bottom-right-radius:4px; }
.received { background:#f1f3f5; color:#333; border-bottom-left-radius:4px; }
.conversation-item { padding:12px; cursor:pointer; border-radius:8px; border-left:3px solid transparent; }
.conversation-item:hover, .conversation-item.active { background:#fff5f5; border-left-color:#dc3545; }
.trainer-avatar { width:45px; height:45px; border-radius:50%; object-fit:cover; }
.conversation-list { max-height:calc(100vh - 350px); overflow-y:auto; }
.typing-indicator { font-style:italic; color:#6c757d; font-size:0.9rem; }
.dropdown-menu { z-index: 1050 !important; }
.navbar { z-index: 1049 !important; }
@media (max-width: 768px) {
    main { margin-left:0; padding:10px; }
    .chat-container { flex-direction:column; height:auto; gap:10px; }
    .col-md-4, .col-md-8 { width:100% !important; }
    .chat-box { height:300px; max-height:300px; }
    .conversation-list { max-height:200px; }
    .message { max-width:85%; }
}
</style>
</head>

<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main style="display:flex; flex-direction:column; height:calc(100vh - 56px); overflow:hidden; padding:0;">
<div style="display:flex; flex-direction:row; flex:1; overflow:hidden;">

<h3 class="fw-bold text-danger" style="position:absolute; top:70px; left:280px; z-index:10; margin:0; padding:15px;">
    <i class="bi bi-chat-dots me-2"></i>Chat Support
</h3>

<!-- LEFT SIDEBAR -->
<div style="width:30%; display:flex; flex-direction:column; min-width:250px; background:#fff; border-right:1px solid #dee2e6; overflow:hidden;">
<div style="padding:20px; border-bottom:1px solid #dee2e6; flex-shrink:0;">
    <h6 class="text-danger mb-0"><i class="bi bi-people-fill me-2"></i>Your Conversations <span class="badge bg-danger" id="conversationCount"><?= $conversations_result->num_rows ?></span></h6>
</div>

<div style="flex:1; overflow-y:auto; padding:0;">
<?php if ($conversations_result->num_rows > 0): ?>
<ul class="list-group list-group-flush" style="border:none;">
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
$other_id = $conv['opposite_id'];
?>
<li class="list-group-item conversation-item" onclick="startConversation(<?= $conv['conversation_id'] ?>, <?= $other_id ?>, '<?= addslashes($conv['opposite_name']) ?>', '<?= $avatarPath ?>', '<?= ucfirst($conv['opposite_role']) ?>')" style="cursor:pointer; padding:12px; border-left:4px solid transparent;">
    <div class="d-flex align-items-center">
        <div style="position:relative; margin-right:12px;">
            <img src="<?= $avatarPath ?>" style="width:45px; height:45px; border-radius:50%; object-fit:cover;">
            <span style="position:absolute;bottom:0;right:3px;width:12px;height:12px;border-radius:50%;background:<?= $statusColor ?>;border:2px solid white;"></span>
        </div>
        <div style="flex:1; min-width:0;">
            <strong style="display:block;"><?= htmlspecialchars($conv['opposite_name']) ?></strong>
            <small class="text-muted"><?= ucfirst($conv['opposite_role']) ?></small>
            <small class="text-muted" style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:0.8rem;"><?= htmlspecialchars($conv['last_message'] ?? '') ?></small>
        </div>
    </div>
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

<div style="padding:15px; border-top:1px solid #dee2e6; flex-shrink:0;">
<h6 class="text-danger mb-2"><i class="bi bi-person-plus me-2"></i>Start New Chat</h6>
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
    <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
        <img src="<?= $avatarPath ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:10px;">
        <div style="flex:1; min-width:0;">
            <strong style="display:block;"><?= htmlspecialchars($u['name']) ?></strong>
            <small class="text-muted"><?= ucfirst($u['role']) ?></small>
        </div>
        <button class="btn btn-sm btn-outline-danger" onclick="startConversation(null, <?= $u['id'] ?>, '<?= addslashes($u['name']) ?>', '<?= $avatarPath ?>', '<?= ucfirst($u['role']) ?>')">
            <i class="bi bi-chat-left"></i>
        </button>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <small class="text-muted">No new users available</small>
<?php endif; ?>
</div>
</div>

<!-- RIGHT SIDE – Chat Box -->
<div style="flex:1; display:flex; flex-direction:column; overflow:hidden; margin-top:60px;">
<div style="display:flex; align-items:center; padding:15px; border-bottom:1px solid #dee2e6; flex-shrink:0; background:#fff;">
    <img id="chatAvatar" src="../../assets/default-avatar.png" style="width:50px; height:50px; border-radius:50%; object-fit:cover; margin-right:15px;">
    <div style="flex:1;">
        <h6 id="chatName" class="fw-bold m-0">Select a conversation</h6>
        <small id="chatRole" class="text-muted">Choose a user to start chatting</small>
        <div id="typingIndicator" class="typing-indicator"></div>
    </div>
</div>

<div id="chatBox" style="flex:1; overflow-y:auto; background:#fff; padding:15px; border:none;">
    <div class="text-center text-muted py-5">
        <i class="bi bi-chat-square-text display-4"></i>
        <p class="mt-3">Select a conversation to start messaging</p>
    </div>
</div>

<div style="padding:15px; border-top:1px solid #dee2e6; flex-shrink:0; background:#f8f9fa;">
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
</main>
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
let socket = null;
let typingTimeout = null;
let useSocket = false;
let chatInputStorage = {};

// Initialize Socket.IO connection with fallback to AJAX
function initializeSocket() {
    // Check if Socket.IO is available
    if (typeof io === 'undefined') {
        console.warn('⚠️ Socket.IO not loaded, using AJAX polling');
        useSocket = false;
        return;
    }

    fetch("ajax/get_socket_auth.php")
        .then(res => res.json())
        .then(auth => {
            console.log("🔌 Attempting to connect to WebSocket server at http://localhost:3000...");
            
            socket = io('http://localhost:3000', {
                auth: {
                    user_id: auth.user_id,
                    token: auth.token
                },
                reconnection: true,
                reconnectionDelay: 1000,
                reconnectionDelayMax: 5000,
                reconnectionAttempts: 5,
                transports: ['websocket', 'polling']
            });

            // Connection established
            socket.on('connect', () => {
                console.log('✅ Connected to chat server - LIVE MODE ACTIVE');
                useSocket = true;
                if (document.getElementById('chatBox')) {
                    document.getElementById('chatBox').classList.remove('opacity-50');
                }
            });

            socket.on('connect_error', (error) => {
                console.error('❌ Connection error:', error);
                useSocket = false;
                console.log('⚠️ Falling back to AJAX polling mode');
            });

            // Receive new messages
            socket.on('new_message', (data) => {
                console.log('📨 New message received:', data);
                if (data.conversation_id === conversationId) {
                    appendMessage(data);
                    scrollToBottom();
                    // Update conversation list preview
                    refreshConversationList();
                    // Show toast notification
                    showToastNotification('New Message', `${data.sender_name || 'Someone'} sent you a message`);
                } else {
                    // Show notification for message from other conversation
                    showNewMessageNotification(data);
                    // Update conversation list to show new message
                    refreshConversationList();
                }
            });

            // User typing indicator
            socket.on('user_typing', (data) => {
                if (data.typing && data.user_id !== myUserId) {
                    document.getElementById("typingIndicator").textContent = "typing...";
                } else {
                    document.getElementById("typingIndicator").textContent = "";
                }
            });

            // User online/offline
            socket.on('user_online', (data) => {
                console.log('👤 User ' + data.user_id + ' is online');
            });

            socket.on('user_offline', (data) => {
                console.log('👤 User ' + data.user_id + ' is offline');
            });

            socket.on('disconnect', () => {
                console.warn('⚠️ Disconnected from chat server');
                useSocket = false;
                console.log('📊 Using AJAX polling mode');
                if (document.getElementById('chatBox')) {
                    document.getElementById('chatBox').classList.add('opacity-50');
                }
            });
        })
        .catch(err => {
            console.error('Failed to get auth:', err);
            useSocket = false;
        });
}

/* =========================
   START CONVERSATION
========================= */
window.startConversation = function (convId, userId, name, avatar, role) {

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
        if (socket) {
            socket.emit('join_chat', {
                user_id: myUserId,
                conversation_id: conversationId
            });
        }
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
        if (socket) {
            socket.emit('join_chat', {
                user_id: myUserId,
                conversation_id: conversationId
            });
        }
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
                appendMessage(msg);
            });

            scrollToBottom();
        });
};

/* APPEND MESSAGE TO CHAT BOX */
function appendMessage(msg) {
    let box = document.getElementById("chatBox");
    
    // Remove "no messages" placeholder
    if (box.innerHTML.includes("No messages yet")) {
        box.innerHTML = "";
    }

    let div = document.createElement("div");
    div.className = "message " + (msg.sender_id == myUserId ? "sent" : "received");
    div.innerHTML = `<div>${escapeHtml(msg.message)}</div>
                     <small style="font-size:0.7rem;opacity:0.7;">${formatTime(msg.created_at)}</small>`;
    box.appendChild(div);
}

/* ESCAPE HTML */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/* SCROLL TO BOTTOM */
function scrollToBottom() {
    let box = document.getElementById("chatBox");
    if (box) {
        setTimeout(() => {
            box.scrollTop = box.scrollHeight;
        }, 50);
    }
}

/* SHOW NEW MESSAGE NOTIFICATION */
function showNewMessageNotification(data) {
    // Show browser notification if available
    if ('Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification('New Message', {
            body: `You have a new message. Click to view.`,
            icon: '../../assets/_logo.png',
            tag: `chat-${data.conversation_id}`
        });
        
        notification.onclick = () => {
            window.focus();
            // Find and click the conversation in the list
            const convItems = document.querySelectorAll('.conversation-item');
            convItems.forEach(item => {
                // Get the onclick attribute and parse the conversation ID
                const onclickAttr = item.getAttribute('onclick');
                if (onclickAttr && onclickAttr.includes(data.conversation_id)) {
                    item.click();
                }
            });
            notification.close();
        };
    }
}

/* SHOW TOAST NOTIFICATION */
function showToastNotification(title, message) {
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.style.cssText = 'position:fixed;top:80px;right:20px;z-index:2000;width:350px;max-width:90vw;';
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.className = 'alert alert-success alert-dismissible fade show';
    toast.style.cssText = 'margin-bottom:10px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);border-left:4px solid #28a745;';
    toast.innerHTML = `
        <div style="display:flex;align-items:start;gap:10px;">
            <div style="font-size:1.3rem;"><i class="bi bi-chat-left-text-fill text-success"></i></div>
            <div style="flex:1;">
                <strong>${title}</strong><br>
                <small>${message}</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);
}

/* REFRESH CONVERSATION LIST */
function refreshConversationList() {
    fetch('ajax/get_conversations.php')
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                const list = document.querySelector('.conversation-list ul');
                if (list) {
                    // Update the conversation count badge
                    document.getElementById('conversationCount').textContent = data.length;
                }
            }
        })
        .catch(err => console.log('Could not refresh conversation list:', err));
}
document.getElementById("sendMessageForm").addEventListener("submit", function (e) {
    e.preventDefault();

    let input = document.getElementById("messageInput");
    let message = input.value.trim();

    if (!message || !conversationId) {
        console.warn("⚠️ Message or conversation ID missing");
        return;
    }

    console.log("📤 Sending message - Mode:", useSocket ? "WebSocket" : "AJAX");

    // Optimistic UI update - show message immediately
    const tempMessage = {
        id: 'temp_' + Date.now(),
        conversation_id: conversationId,
        sender_id: myUserId,
        message: message,
        created_at: new Date().toISOString()
    };
    appendMessage(tempMessage);
    scrollToBottom();

    // Clear input
    input.value = "";

    // Send via Socket or AJAX
    if (useSocket && socket && socket.connected) {
        // WebSocket mode (real-time)
        console.log("⚡ Using WebSocket");
        socket.emit('send_message', {
            conversation_id: conversationId,
            sender_id: myUserId,
            message: message
        }, (response) => {
            if (response?.success) {
                console.log("✅ Message saved via WebSocket");
            } else {
                console.error("❌ WebSocket error:", response);
            }
        });
    } else {
        // AJAX mode (polling)
        console.log("📊 Using AJAX polling");
        fetch("ajax/send_message.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: `conversation_id=${conversationId}&message=${encodeURIComponent(message)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log("✅ Message saved via AJAX");
                // Refresh messages after 500ms
                setTimeout(() => loadMessages(), 500);
            } else {
                console.error("❌ AJAX error:", data);
            }
        })
        .catch(err => {
            console.error("❌ AJAX request failed:", err);
        });
    }
});

/* =========================
   TYPING INDICATOR
========================= */
document.getElementById("messageInput").addEventListener("input", function() {
    if (!socket || !conversationId) return;
    
    socket.emit('typing', {
        conversation_id: conversationId,
        user_id: myUserId,
        typing: true
    });

    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        socket.emit('typing', {
            conversation_id: conversationId,
            user_id: myUserId,
            typing: false
        });
    }, 1000);
});

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

// Initialize socket connection on page load
document.addEventListener('DOMContentLoaded', () => {
    initializeSocket();
    
    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Fallback polling - every 2 seconds check for new messages if not using WebSocket
    setInterval(() => {
        if (!useSocket && conversationId) {
            loadMessages();
            refreshConversationList();
        }
    }, 2000);
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (conversationId && socket) {
        socket.emit('leave_chat', {
            user_id: myUserId,
            conversation_id: conversationId
        });
    }
});

</script>


</body>
</html>
 