<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// User details fetch
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// User posts fetch
$stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$posts = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($user['username']); ?> - Profile</title>
</head>
<body>

<h2><?php echo htmlspecialchars($user['full_name']); ?>'s Profile</h2>
<a href="../home.php">Home</a>
<img src="img/profile_img/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" width="100" height="100" style="border-radius:50%;"><br>
<b>Username:</b> <?php echo htmlspecialchars($user['username']); ?><br>
<b>Email:</b> <?php echo htmlspecialchars($user['email']); ?><br>

<a href="edit_profile.php">Edit Profile</a> | <a href="../logout.php">Logout</a>

<hr>
<h3>Your Posts</h3>
<a href="upload_post.php">create post</a>
<?php while ($post = $posts->fetch_assoc()) { ?>
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <img src="../img/posts/<?php echo $post['image']; ?>" width="300"><br>
        <p><?php echo htmlspecialchars($post['caption']); ?></p>
    </div>
<?php } ?>

</body>
</html>
