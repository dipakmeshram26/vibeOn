<?php
session_start();
include 'db.php'; // DB connection

if (!isset($_GET['id'])) {
    die("User not found.");
}

$profile_id = intval($_GET['id']);
$current_user = $_SESSION['user_id'] ?? 0;

// User info
$user_sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Account type
$is_private = $user['is_private']; // 0 = public, 1 = private


$current_user_id = $_SESSION['user_id'];
$profile_id = $_GET['id']; // jiski profile khol rahe hai

// Fetch profile user info
$stmt = $conn->prepare("SELECT id, username, is_private FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Check friendship (agar follow system hai to follow table check karo)
// Check friendship (accepted follow relation hai ya nahi)
$stmt = $conn->prepare("SELECT 1 FROM follows 
                        WHERE follower_id = ? AND following_id = ? 
                        AND status = 'accepted'");
$stmt->bind_param("ii", $current_user_id, $profile_id);
$stmt->execute();
$is_friend = $stmt->get_result()->num_rows > 0;




// Posts
$post_sql = "SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($post_sql);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$posts = $stmt->get_result();

// Followers count
$follower_count = $conn->query("SELECT COUNT(*) AS total FROM follows WHERE following_id = $profile_id")->fetch_assoc()['total'];
$following_count = $conn->query("SELECT COUNT(*) AS total FROM follows WHERE follower_id = $profile_id")->fetch_assoc()['total'];

// Follow status
$is_following = $conn->query("SELECT * FROM follows WHERE follower_id = $current_user AND following_id = $profile_id AND status='accepted'")->num_rows > 0;

// Follow request status (if private)

// Follow request status (if private)
$is_requested = false;
if ($is_private && !$is_following && $current_user) {
    $req_sql = "SELECT * FROM follows WHERE follower_id = ? AND following_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($req_sql);
    $stmt->bind_param("ii", $current_user, $profile_id);
    $stmt->execute();
    $is_requested = $stmt->get_result()->num_rows > 0;
}



// Highlights
$highlight_sql = "SELECT * FROM highlights WHERE user_id = ?";
$stmt = $conn->prepare($highlight_sql);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$highlights = $stmt->get_result();
?>
<!DOCTYPE html>
<html>

<head>
    <title><?= htmlspecialchars($user['username']) ?> - Profile</title>
    <style>
        body {
            background: #000;
            color: white;
            font-family: Arial;
        }

        .profile-header {
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .profile-header img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-right: 20px;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .highlights {
            display: flex;
            gap: 15px;
            margin: 20px;
        }

        .highlights div {
            text-align: center;
        }

        .highlights img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid gray;
        }

        .posts {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2px;
        }

        .posts img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        button {
            background: #0095f6;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="profile-header">
        <img src="../img/profile_img/<?= htmlspecialchars($user['profile_picture'] ?: 'default.png') ?>"
            alt="Profile Picture">

        <div>
            <h2><?= htmlspecialchars($user['username']) ?></h2>



            <?php if ($current_user && $current_user != $profile_id): ?>
                <?php if ($is_private && !$is_following): ?>
                    <?php if ($is_requested): ?>
                        <button disabled data-action="requested">Requested</button>
                    <?php else: ?>
                        <button id="followBtn" onclick="sendRequest()" data-action="follow">Follow</button>
                    <?php endif; ?>
                <?php else: ?>
                    <button id="followBtn" onclick="toggleFollow()" data-action="<?= $is_following ? 'unfollow' : 'follow' ?>">
                        <?= $is_following ? 'Unfollow' : 'Follow' ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>


            <?php if ($profile['is_private'] == 0): ?>
                <!-- Public profile → msg button hamesha -->
                <button onclick="openChatBox(<?php echo $profile_id; ?>)">Message</button>

            <?php elseif ($profile['is_private'] == 1 && $is_friend): ?>
                <!-- Private but friend hai -->
                <button onclick="openChatBox(<?php echo $profile_id; ?>)">Message</button>

            <?php else: ?>
                <!-- Private + not friend → no button -->
                <p>This account is private.</p>
            <?php endif; ?>


            <div id="chatBox"
                style="display:none; position:fixed; bottom:20px; right:20px; width:300px; background:#fff; border:1px solid #ccc; border-radius:10px; padding:10px;">
                <div id="chatMessages"
                    style="height:200px; overflow-y:auto; border-bottom:1px solid #ddd; margin-bottom:10px;"></div>
                <form id="chatForm">
                    <input type="hidden" name="receiver_id" id="receiver_id">
                    <input type="text" name="message" id="messageInput" placeholder="Type a message..." required
                        style="width:80%;">
                    <button type="submit">Send</button>
                </form>
            </div>



            <div class="stats">
                <span><b><?= $posts->num_rows ?></b> posts</span>
                <span><b><?= $follower_count ?></b> followers</span>
                <span><b><?= $following_count ?></b> following</span>
            </div>
            <p><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
        </div>
    </div>

    <div class="highlights">
        <?php while ($h = $highlights->fetch_assoc()): ?>
            <div>
                <img src="<?= htmlspecialchars($h['icon']) ?>" alt="<?= htmlspecialchars($h['title']) ?>">
                <p><?= htmlspecialchars($h['title']) ?></p>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="posts">
        <?php if ($is_private && !$is_following): ?>
            <p style="text-align:center; margin-top:20px; color:gray;">
                🔒 This account is private.<br>Follow to see their photos and videos.
            </p>
        <?php else: ?>
            <?php while ($p = $posts->fetch_assoc()): ?>
                <img src="../img/posts/<?= htmlspecialchars($p['image']) ?>" alt="Post">
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <script>
        function openChatBox(userId) {
            document.getElementById('chatBox').style.display = 'block';
            document.getElementById('receiver_id').value = userId;
            loadMessages(userId);
        }

        function loadMessages(userId) {
            fetch("load_messages.php?user=" + userId)
                .then(res => res.text())
                .then(data => {
                    document.getElementById("chatMessages").innerHTML = data;
                });
        }

        document.getElementById("chatForm").addEventListener("submit", function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            fetch("send_message.php", { method: "POST", body: formData })
                .then(() => {
                    document.getElementById("messageInput").value = "";
                    loadMessages(formData.get("receiver_id"));
                });
        });
    </script>



    <script>
        function toggleFollow() {
            let btn = document.getElementById('followBtn');
            let action = btn.dataset.action; // "follow" | "unfollow"

            fetch('follow_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=<?= $profile_id ?>&action=' + action
            })
                .then(res => res.text())
                .then(data => {
                    if (data === 'followed') {
                        btn.innerText = 'Unfollow';
                        btn.dataset.action = 'unfollow';
                    } else if (data === 'unfollowed') {
                        btn.innerText = 'Follow';
                        btn.dataset.action = 'follow';
                    }
                });
        }

        function sendRequest() {
            let btn = document.getElementById('followBtn');

            fetch('follow_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=<?= $profile_id ?>'
            })
                .then(res => res.text())
                .then(data => {
                    if (data === 'requested') {
                        btn.innerText = 'Requested';
                        btn.dataset.action = 'requested';
                        btn.disabled = true;
                    }
                });
        }
    </script>



</body>

</html>