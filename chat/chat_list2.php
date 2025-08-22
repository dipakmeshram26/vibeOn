<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']))
    exit("Not logged in");

$current_user = (int) $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>VibeOn Chat</title>
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../homestyle.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            margin: 0;
            font-family: sans-serif;
            background: #0c0c0c;
            color: #fff
        }

        .chat-container {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* Left chat list */
        .chat-list {
            width: 260px;
            border-right: 1px solid #222;
            overflow: auto;
            background: #0f0f0f
        }

        .chat-list-item {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #222;
            display: flex;
            align-items: center;
            gap: 10px
        }

        .chat-list-item:hover {
            background: #1a1a1a
        }

        .chat-list-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%
        }

        .chat-list-item .name {
            font-weight: 600
        }

        .chat-list-item .time {
            margin-left: auto;
            font-size: 12px;
            opacity: .7
        }

        /* Center chat window */
        .chat-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            border: 1px solid beige;
            margin: 7px;
        }

        .chat-top {
            padding: 12px 16px;
            /* margin: 2px 1px 12px; */
            border-bottom: 1px solid #222;
            font-weight: 600;
            background: #201f1fff;
            /* box-shadow: 0 0 10px white; */
            border: 0.3px solid white;

        }

        #chatBox {
            flex: 1;
            overflow: auto;
            padding: 14px;
            display: flex;
            flex-direction: column;

            gap: 8px;
            background: #111
        }

        .bubble {
            max-width: 70%;
            padding: 10px 12px;
            border-radius: 14px;
            line-height: 1.3;
            word-wrap: break-word
        }

        .me {
            align-self: flex-end;
            background: #1d72f3;
            color: #fff;
            border-bottom-right-radius: 6px
        }

        .them {
            align-self: flex-start;
            background: #1a1a1a;
            color: #fff;
            border-bottom-left-radius: 6px;
            border: 1px solid #2a2a2a
        }

        .meta {
            font-size: 11px;
            opacity: .7;
            margin-top: 4px
        }

        .composer {
            display: flex;
            gap: 8px;
            border-top: 1px solid #222;
            padding: 10px;
            background: #0c0c0c
        }

        .composer input {
            flex: 1;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #333;
            background: #111;
            color: #fff;
            outline: none
        }

        .composer button {
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            background: #0095f6;
            color: #fff;
            font-weight: 600;
            cursor: pointer
        }

        /* Right profile section */
        .profile-section {
            width: 280px;
            background: #0f0f0f;
            padding: 20px;
            display: none;
            flex-direction: column;
            align-items: center;
            text-align: center
        }

        .profile-section img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 15px
        }

        .profile-section .username {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 6px
        }

        .profile-section .bio {
            font-size: 14px;
            opacity: .8
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- LEFT SIDEBAR -->
        <div class="sidebar-left">
            <h2>VibeOn</h2>
            <div class="nav">
                <a href="home.php">Home</a>
                <a href="home.php">Search</a>

                <a href="/vibeOn/chat/chat_list.php">Messages</a>
                <a href="home.php">Notification</a>
                <a href="home.php">More</a>
                <a href="profile_pages/profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>



        <!-- MAIN CONTENT -->
        <div class="main-content">

            <form method="GET" action="search.php" style="margin-bottom:20px;">
                <input type="text" name="q" placeholder="Search by name or ID..." required
                    style="padding:8px; width:250px; border:1px solid #ccc; border-radius:5px;">
                <button type="submit"
                    style="padding:8px 12px; background:#0095f6; color:white; border:none; border-radius:5px;">
                    Search
                </button>
            </form>

            <div class="chat-container">

                <!-- LEFT: Chat List -->
                <div class="chat-list" id="chatList">
                    <?php
                    $stmt = $conn->prepare("
            SELECT u.id, u.username, u.profile_picture, MAX(m.created_at) as last_msg_time
            FROM messages m
            JOIN users u 
              ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
            WHERE m.sender_id = ? OR m.receiver_id = ?
            GROUP BY u.id, u.username, u.profile_picture
            ORDER BY last_msg_time DESC
        ");
                    $stmt->bind_param("iii", $current_user, $current_user, $current_user);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                        $time = date("h:i A", strtotime($row['last_msg_time']));
                        ?>
                        <div class="chat-list-item"
                            onclick="openChat(<?= $row['id'] ?>,'<?= htmlspecialchars($row['username']) ?>','<?= htmlspecialchars($row['profile_picture'] ?? 'default.png') ?>')">
                            <img src="../img/profile_img/<?= htmlspecialchars($row['profile_picture'] ?? 'default.png') ?>">
                            <div>
                                <div class="name"><?= htmlspecialchars($row['username']) ?></div>
                            </div>
                            <div class="time"><?= $time ?></div>
                        </div>
                    <?php } ?>
                </div>

                <!-- CENTER: Chat Window -->
                <div class="chat-wrap" id="chatWrap" style="display:none;">
                    <div class="chat-top" id="chatTop">Select a chat</div>
                    <div id="chatBox"></div>
                    <div class="composer">
                        <input type="text" id="chatInput" placeholder="Type a message...">
                        <button id="sendBtn">Send</button>
                    </div>
                </div>

                <!-- RIGHT: Profile Section -->
                <!-- Profile Section -->
                <div class="profile-section" id="profileSection">
                    <img id="profilePic" src="../img/profile_img/default.png">
                    <div class="username" id="profileName">Select a user</div>
                    <div class="bio" id="profileBio">Profile info will appear here</div>

                    <!-- Buttons -->
                    <button onclick="deleteChat(chatWith)" class="delete-btn">
                        🗑 Delete Chat
                    </button>
                    <button onclick="deleteContact(chatWith)" class="delete-btn">
                        ❌ Delete Contact
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        let currentUser = <?= $current_user ?>;
        let chatWith = 0;
        let lastId = 0;

        function openChat(uid, uname, upic) {
            chatWith = uid;
            lastId = 0;
            document.getElementById("chatWrap").style.display = "flex";
            document.getElementById("chatTop").textContent = uname;
            document.getElementById("chatBox").innerHTML = "";
            loadMessages(true);

            // show profile on right
            document.getElementById("profileSection").style.display = "flex";
            document.getElementById("profileName").textContent = uname;
            document.getElementById("profilePic").src = "../img/profile_img/" + upic;
            document.getElementById("profileBio").textContent = "This is " + uname + "'s bio/info.";
        }

        // append message bubble
        function appendMessage(m) {
            const box = document.getElementById('chatBox');
            const wrap = document.createElement('div');
            wrap.className = 'bubble ' + (m.type === 'self' ? 'me' : 'them');
            wrap.innerHTML = `<div>${m.text}</div><div class="meta">${(m.type === 'self' ? 'You' : m.sender)} • ${m.created}</div>`;
            box.appendChild(wrap);
        }

        // load messages
        function loadMessages(initial = false) {
            if (!chatWith) return;
            fetch("load_messages.php?receiver_id=" + chatWith)
                .then(r => r.json())
                .then(list => {
                    if (!Array.isArray(list)) return;
                    const box = document.getElementById('chatBox');
                    const atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 20;
                    list.forEach(m => {
                        if (m.id > lastId) { appendMessage(m); lastId = m.id; }
                    });
                    if (initial || atBottom) box.scrollTop = box.scrollHeight;
                });
        }

        // poll
        setInterval(() => loadMessages(), 2000);

        // send message
        document.getElementById('sendBtn').onclick = function () {
            const inp = document.getElementById('chatInput');
            const msg = inp.value.trim();
            if (!msg || !chatWith) return;
            fetch("send_message.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "receiver_id=" + encodeURIComponent(chatWith) + "&message=" + encodeURIComponent(msg)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        inp.value = "";
                        loadMessages();
                    }
                });
        };

        // enter to send
        document.getElementById('chatInput').addEventListener('keydown', e => {
            if (e.key === "Enter") { e.preventDefault(); document.getElementById('sendBtn').click(); }
        });
    </script>

    <script>
        // Sirf chat delete karne ke liye
        function deleteChat(uid) {
            if (!confirm("Delete all messages with this user?")) return;

            fetch("delete_chat.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "other_user_id=" + encodeURIComponent(uid)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        document.getElementById("chatBox").innerHTML = "";
                        alert("All messages deleted!");
                    } else {
                        alert("Error: " + data.error);
                    }
                });
        }

        // Contact + messages delete karne ke liye
        function deleteContact(uid) {
            if (!confirm("Delete this contact and all messages?")) return;

            fetch("delete_contact.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "other_user_id=" + encodeURIComponent(uid)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        // List se bhi remove karna
                        document.querySelector(`.chat-user[data-id="${uid}"]`)?.remove();
                        document.getElementById("chatBox").innerHTML = "";
                        document.getElementById("profileSection").style.display = "none";
                        alert("Contact deleted!");
                    } else {
                        alert("Error: " + data.error);
                    }
                });
        }

    </script>
</body>

</html>