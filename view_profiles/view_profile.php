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
$is_following = $conn->query("SELECT * FROM follows WHERE follower_id = $current_user AND following_id = $profile_id")->num_rows > 0;

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
        body { background: #000; color: white; font-family: Arial; }
        .profile-header { display: flex; align-items: center; padding: 20px; }
        .profile-header img { width: 120px; height: 120px; border-radius: 50%; margin-right: 20px; }
        .stats { display: flex; gap: 20px; margin-top: 10px; }
        .highlights { display: flex; gap: 15px; margin: 20px; }
        .highlights div { text-align: center; }
        .highlights img { width: 60px; height: 60px; border-radius: 50%; border: 2px solid gray; }
        .posts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; }
        .posts img { width: 100%; height: 300px; object-fit: cover; }
        button { background: #0095f6; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<div class="profile-header">
    <img src="../img/profile_img/<?= htmlspecialchars($user['profile_picture'] ?: 'default.png') ?>" alt="Profile Picture">

    <div>
        <h2><?= htmlspecialchars($user['username']) ?></h2>
        <?php if ($current_user && $current_user != $profile_id): ?>
            <button id="followBtn" onclick="toggleFollow()"><?= $is_following ? 'Unfollow' : 'Follow' ?></button>
        <?php endif; ?>
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
    <?php while ($p = $posts->fetch_assoc()): ?>
        <img src="../img/posts/<?= htmlspecialchars($p['image']) ?>" alt="Post">
    <?php endwhile; ?>
</div>

<script>
function toggleFollow() {
    let btn = document.getElementById('followBtn');
    let action = btn.innerText.toLowerCase();

    fetch('follow_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=<?= $profile_id ?>&action=' + action
    })
    .then(res => res.text())
    .then(data => {
        if (data === 'followed') {
            btn.innerText = 'Unfollow';
        } else if (data === 'unfollowed') {
            btn.innerText = 'Follow';
        }
    });
}
</script>

</body>
</html>
