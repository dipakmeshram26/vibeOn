<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) exit("Not logged in");

$current_user = (int)$_SESSION['user_id'];
$chat_with    = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Username
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $chat_with);
$stmt->execute();
$stmt->bind_result($chat_username);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome - VibeOn</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="homestyle.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Minimal chat styles to ensure correct layout */
        .chat-wrap{max-width:700px;margin:20px auto;background:#0f0f0f;border:1px solid #222;border-radius:10px;display:flex;flex-direction:column;height:70vh}
        .chat-top{padding:12px 16px;border-bottom:1px solid #222;font-weight:600}
        #chatBox{flex:1;overflow:auto;padding:14px;display:flex;flex-direction:column;gap:8px}
        .bubble{max-width:70%;padding:10px 12px;border-radius:14px;line-height:1.3;word-wrap:break-word}
        .me{align-self:flex-end;background:#1d72f3;color:#fff;border-bottom-right-radius:6px}
        .them{align-self:flex-start;background:#1a1a1a;color:#fff;border-bottom-left-radius:6px;border:1px solid #2a2a2a}
        .meta{font-size:11px;opacity:.7;margin-top:4px}
        .composer{display:flex;gap:8px;border-top:1px solid #222;padding:10px;background:#0c0c0c}
        .composer input{flex:1;padding:10px 12px;border-radius:8px;border:1px solid #333;background:#111;color:#fff;outline:none}
        .composer button{padding:10px 14px;border:none;border-radius:8px;background:#0095f6;color:#fff;font-weight:600;cursor:pointer}
        .composer button:disabled{opacity:.6;cursor:not-allowed}
    </style>
</head>
<body>

<div class="chat-wrap">
    <div class="chat-top">Chat with <?= htmlspecialchars($chat_username ?: 'User #'.$chat_with) ?></div>

    <div id="chatBox"></div>

    <div class="composer">
        <input type="text" id="chatInput" placeholder="Type a message...">
        <button id="sendBtn" onclick="sendMessage(<?= $chat_with ?>)">Send</button>
    </div>
</div>

<script>
let lastId = 0;
let loading = false;

function appendMessage(m){
    const box = document.getElementById('chatBox');
    const wrap = document.createElement('div');
    wrap.className = 'bubble ' + (m.type === 'self' ? 'me' : 'them');

    // main text
    const txt = document.createElement('div');
    txt.textContent = m.text; // SAFE: textContent avoids HTML injection
    wrap.appendChild(txt);

    // optional meta (sender / time)
    const meta = document.createElement('div');
    meta.className = 'meta';
    meta.textContent = (m.type === 'self' ? 'You' : m.sender) + ' • ' + (m.created || '');
    wrap.appendChild(meta);

    box.appendChild(wrap);
}

function loadMessages(initial=false){
    if(loading) return;
    loading = true;

    fetch("load_messages.php?receiver_id=<?= $chat_with ?>")
        .then(r => r.json())
        .then(list => {
            if(!Array.isArray(list)) return;
            const box = document.getElementById('chatBox');
            // keep scroll if user is already at bottom
            const atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 20;

            // append only new ones
            list.forEach(m => {
                if (m.id > lastId) appendMessage(m);
                if (m.id > lastId) lastId = m.id;
            });

            // on first load or if user was at bottom, stick to bottom
            if (initial || atBottom) {
                box.scrollTop = box.scrollHeight;
            }
        })
        .catch(()=>{})
        .finally(()=> loading = false);
}

// initial load + polling
loadMessages(true);
setInterval(loadMessages, 2000);

// enter to send
const input = document.getElementById('chatInput');
input.addEventListener('keydown', (e)=>{
    if(e.key === 'Enter'){
        e.preventDefault();
        document.getElementById('sendBtn').click();
    }
});

function sendMessage(receiver){
    const btn = document.getElementById('sendBtn');
    const inp = document.getElementById('chatInput');
    const msg = inp.value.trim();
    if(!msg) return;

    btn.disabled = true;

    fetch("send_message.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "receiver_id="+encodeURIComponent(receiver)+"&message="+encodeURIComponent(msg)
    })
    .then(r=>r.json())
    .then(data=>{
        if(data && data.ok){
            inp.value = "";
            // optimistic append is optional; load again to be safe
            loadMessages();
        }
    })
    .finally(()=> btn.disabled = false);
}
</script>
</body>
</html>
