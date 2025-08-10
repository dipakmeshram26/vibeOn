<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

// No need for this if you want to show all posts from all users
// If you want posts only from logged-in user, uncomment below:
// $user_id = $_SESSION['user_id'];
// $stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
// $stmt->bind_param("i", $user_id);
// $stmt->execute();
// $posts = $stmt->get_result();

// Get all posts with user info
$result = $conn->query("
    SELECT posts.*, users.username, users.profile_picture,
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Welcome - VibeOn</title>
    <style>
        .post {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            width: 350px;
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .post-header img {
            border-radius: 50%;
        }

        .post img.post-image {
            margin-top: 10px;
            width: 100%;
        }
    </style>
</head>

<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> 👋</h2>
    <a href="home.php">Home</a>
    <p>You are now logged in as <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></p>
    <a href="profile_pages/profile.php">profile</a>
    <a href="logout.php">Logout</a>

    <hr>

    <?php
    // Loop through all posts
    while ($row = $result->fetch_assoc()) {
        ?>
        <div class='post'>
            <div class='post-header'>
                <img src='img/profile_img/<?php echo htmlspecialchars($row['profile_picture']); ?>' width='40' height='40'
                    alt="Profile Picture">
                <b><?php echo htmlspecialchars($row['username']); ?></b>
            </div>

            <img class='post-image' src='img/posts/<?php echo htmlspecialchars($row['image']); ?>' alt="Post Image">

            <?php if (!empty($row['caption'])): ?>
                <p><?php echo htmlspecialchars($row['caption']); ?></p>
            <?php endif; ?>

            <!-- Like Button -->
            <button class="like-btn" data-post-id="<?php echo $row['id']; ?>">❤️ Like</button>
            <span id="like-count-<?php echo $row['id']; ?>"><?php echo $row['like_count']; ?></span>

            <!-- Comment Form -->
            <form class="comment-form" data-post-id="<?php echo $row['id']; ?>">
                <input type="text" name="comment" placeholder="Add a comment..." required>
                <button type="submit">Post</button>
            </form>

            <!-- Show comment count -->
            <div>
                Comments (<?php echo $row['comment_count']; ?>)
            </div>

            <!-- Optionally: fetch and show actual comments below -->
            <div class="comments" id="comments-<?php echo $row['id']; ?>">
                <?php
                // Fetch comments for this post
                $comments_result = $conn->query("
                SELECT comments.comment, users.username 
                FROM comments 
                JOIN users ON comments.user_id = users.id 
                WHERE comments.post_id = " . intval($row['id']) . " 
                ORDER BY comments.created_at ASC
            ");
                while ($comment = $comments_result->fetch_assoc()) {
                    echo "<p><b>" . htmlspecialchars($comment['username']) . ":</b> " . htmlspecialchars($comment['comment']) . "</p>";
                }
                ?>
            </div>
        </div>
        <?php
    }

    ?>





    <script>
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                let postId = btn.getAttribute('data-post-id');
                fetch('like_post.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'post_id=' + postId
                }).then(res => res.text())
                    .then(data => {
                        document.getElementById('like-count-' + postId).innerText = data;
                    });
            });
        });

        document.querySelectorAll('.comment-form').forEach(form => {
            form.addEventListener('submit', e => {
                e.preventDefault();
                let postId = form.getAttribute('data-post-id');
                let comment = form.querySelector('input[name="comment"]').value;
                fetch('add_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'post_id=' + postId + '&comment=' + encodeURIComponent(comment)
                }).then(res => res.text())
                    .then(data => {
                        document.getElementById('comments-' + postId).innerHTML += data;
                        form.reset();
                    });
            });
        });
    </script>

</body>

</html>