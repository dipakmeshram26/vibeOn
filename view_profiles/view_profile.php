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



// Profile details fetch
$stmt = $conn->prepare("SELECT username, is_private FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();


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

            <button onclick="openChatBox(<?php echo $profile_id; ?>)">Message</button>





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


    <h2><?php echo htmlspecialchars($profile['username']); ?>'s Profile</h2>

    <?php
    // Private check
    if ($profile['is_private']) {
        // check follow status
        $stmt = $conn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $current_user_id, $profile_id);
        $stmt->execute();
        $is_friend = $stmt->get_result()->num_rows > 0;

        if ($is_friend) {
            echo '<button onclick="openChatBox(' . $profile_id . ')">Message</button>';
        } else {
            echo '<button onclick="alert(\'This profile is private. Please send a follow request.\')">Message</button>';
        }
    } else {
        // Public account
        echo '<button onclick="openChatBox(' . $profile_id . ')">Message</button>';
    }
    ?>

    <!-- Chat Box Container -->
    <div id="chatBox"
        style="display:none; position:fixed; bottom:20px; right:20px; width:300px; border:1px solid #ccc; background:#fff; padding:10px;">
        <div id="chatMessages"
            style="height:200px; overflow-y:scroll; border-bottom:1px solid #ddd; margin-bottom:10px;"></div>
        <form onsubmit="sendMessage(<?php echo $profile_id; ?>); return false;">
            <input type="text" id="chatInput" placeholder="Type a message..." style="width:80%">
            <button type="submit">Send</button>
        </form>
        <button onclick="document.getElementById('chatBox').style.display='none'">Close</button>
    </div>

    <script>
        function openChatBox(profileId) {
            document.getElementById("chatBox").style.display = "block";
            loadMessages(profileId);
        }

        // Fetch messages
        function loadMessages(profileId) {
            fetch("load_messages.php?receiver_id=" + profileId)
                .then(res => res.text())
                .then(data => {
                    document.getElementById("chatMessages").innerHTML = data;
                });
        }

        // Send message
        function sendMessage(profileId) {
            let msg = document.getElementById("chatInput").value;
            fetch("send_message.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "receiver_id=" + profileId + "&message=" + encodeURIComponent(msg)
            }).then(() => {
                document.getElementById("chatInput").value = "";
                loadMessages(profileId);
            });
        }
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