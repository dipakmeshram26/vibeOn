<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../db.php';

if (!isset($_SESSION['user_id'])) { exit; }
$me = (int)$_SESSION['user_id'];
?>
<style>
  /* Messages widget */
  .msg-widget{position:fixed;right:20px;bottom:20px;width:320px;max-height:70vh;background:#111;
    color:#fff;border:1px solid #222;border-radius:12px;overflow:hidden;font-family:Arial, sans-serif;z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,.45)}
  .msg-header{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #222;background:#0d0d0d;font-weight:700}
  .msg-toggle{cursor:pointer;font-size:14px;opacity:.9}
  .msg-body{display:none}
  .msg-list{max-height:45vh;overflow:auto}
  .msg-item{display:flex;gap:10px;padding:10px 12px;border-bottom:1px solid #1e1e1e;cursor:pointer}
  .msg-item:hover{background:#161616}
  .msg-item img{width:40px;height:40px;border-radius:50%;object-fit:cover}
  .msg-meta{display:flex;flex-direction:column;flex:1;min-width:0}
  .msg-line1{display:flex;justify-content:space-between;gap:8px}
  .msg-username{font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .msg-time{font-size:12px;color:#aaa;white-space:nowrap}
  .msg-snippet{font-size:13px;color:#bbb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}

  .chat-box{border-top:1px solid #222}
  .chat-head{padding:8px 12px;border-bottom:1px solid #222;display:flex;align-items:center;gap:10px}
  .chat-head img{width:28px;height:28px;border-radius:50%;object-fit:cover}
  .chat-title{font-weight:700;font-size:14px}
  .chat-area{height:240px;overflow:auto;padding:10px;background:#0d0d0d}
  .bubble{max-width:75%;margin:6px 0;padding:8px 10px;border-radius:10px;font-size:14px;line-height:1.35;word-break:break-word}
  .me{background:#1f5eff;margin-left:auto}
  .them{background:#222}
  .chat-form{display:flex;gap:8px;padding:10px;border-top:1px solid #222;background:#0d0d0d}
  .chat-form input[type=text]{flex:1;background:#141414;color:#eee;border:1px solid #2a2a2a;border-radius:8px;padding:10px;font-size:14px}
  .chat-form button{background:#0095f6;border:none;color:#fff;font-weight:700;border-radius:8px;padding:10px 12px;cursor:pointer}
</style>

<div class="msg-widget" id="msgWidget">
  <div class="msg-header">
    <div>Messages</div>
    <div class="msg-toggle" onclick="toggleMsgBody()">▲</div>
  </div>

  <div class="msg-body" id="msgBody">
    <!-- Thread list -->
    <div class="msg-list" id="threadList">
      <!-- Filled by fetch via AJAX -->
    </div>

    <!-- Chat box -->
    <div class="chat-box" id="chatBox" style="display:none;">
      <div class="chat-head">
        <img id="chatAvatar" src="" alt="">
        <div class="chat-title" id="chatTitle">Chat</div>
      </div>
      <div class="chat-area" id="chatArea"></div>
      <form class="chat-form" id="chatForm" onsubmit="return sendMessage();">
        <input type="hidden" id="toUserId" value="">
        <input type="text" id="msgInput" placeholder="Message..." required>
        <button type="submit">Send</button>
      </form>
    </div>
  </div>
</div>

<script>
  const msgBody = document.getElementById('msgBody');
  const toggleBtn = document.querySelector('.msg-toggle');
  const threadList = document.getElementById('threadList');
  const chatBox   = document.getElementById('chatBox');
  const chatArea  = document.getElementById('chatArea');
  const chatTitle = document.getElementById('chatTitle');
  const chatAvatar= document.getElementById('chatAvatar');
  const toUserId  = document.getElementById('toUserId');
  const msgInput  = document.getElementById('msgInput');

  function toggleMsgBody(){
    const open = msgBody.style.display === 'block';
    msgBody.style.display = open ? 'none' : 'block';
    toggleBtn.textContent = open ? '▲' : '▼';
  }
  // Open by default
  toggleMsgBody(); toggleMsgBody();

  // Load threads (chat list)
  function loadThreads(){
    fetch('messages/fetch_threads.php')
      .then(r=>r.text()).then(html=>{
        threadList.innerHTML = html;
        // attach click
        document.querySelectorAll('.msg-item').forEach(it=>{
          it.addEventListener('click', ()=>{
            openChat(it.dataset.uid, it.dataset.username, it.dataset.avatar);
          });
        });
      });
  }

  // Open a chat
  function openChat(uid, username, avatar){
    toUserId.value = uid;
    chatTitle.textContent = '@'+username;
    chatAvatar.src = avatar;
    chatBox.style.display = 'block';
    chatArea.innerHTML = '';
    loadMessages();
  }

  // Load messages of current chat
  function loadMessages(){
    const uid = toUserId.value;
    if(!uid) return;
    fetch('messages/fetch_messages.php?with='+encodeURIComponent(uid))
      .then(r=>r.json()).then(rows=>{
        chatArea.innerHTML = '';
        rows.forEach(m=>{
          const div = document.createElement('div');
          div.className = 'bubble ' + (m.is_me ? 'me':'them');
          div.textContent = m.content;
          chatArea.appendChild(div);
        });
        chatArea.scrollTop = chatArea.scrollHeight;
      });
  }

  // Send message
  function sendMessage(){
    const uid = toUserId.value;
    const content = msgInput.value.trim();
    if(!uid || !content) return false;

    const form = new URLSearchParams();
    form.set('to', uid);
    form.set('content', content);

    fetch('messages/send_message.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: form.toString()
    })
    .then(r=>r.json()).then(res=>{
      if(res.ok){
        msgInput.value = '';
        loadMessages();
        loadThreads();
      }
    });
    return false;
  }

  // Polling
  setInterval(()=>{
    loadThreads();
    loadMessages();
  }, 4000);

  // initial
  loadThreads();
</script>
