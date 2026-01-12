let currentConversationId = null;

function loadMessages(convoId, otherId, name, avatar, role){
    currentConversationId = convoId;
    $("#chatName").text(name);
    $("#chatAvatar").attr("src", avatar);
    $("#chatRole").text(role);
    $("#conversation_id").val(convoId);
    $("#messageInput").prop("disabled", false);
    $("button[type='submit']").prop("disabled", false);

    $.get("load_messages.php", {conversation_id: convoId}, function(data){
        $("#chatBox").html(data);
        scrollToBottom();
    });
}

function startConversation(userId){
    $.post("start_conversation.php", {trainer_id: userId}, function(){
        location.reload();
    });
}

$("#sendMessageForm").submit(function(e){
    e.preventDefault();
    const msg = $("#messageInput").val().trim();
    if (!msg || !currentConversationId) return;

    $.post("send_message.php", {conversation_id: currentConversationId, message: msg}, function(){
        $("#messageInput").val("");
        loadMessages(currentConversationId);
    });
});

function scrollToBottom(){
    const chatBox = document.getElementById("chatBox");
    chatBox.scrollTop = chatBox.scrollHeight;
}
