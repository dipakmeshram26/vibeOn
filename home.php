<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

// Fetch statuses
$statuses = $conn->query("
    SELECT s.*, u.username, u.profile_picture 
    FROM statuses s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.created_at DESC
");

$statuses = $conn->query("
    SELECT s.*, u.username, u.profile_picture 
    FROM statuses s
    JOIN users u ON s.user_id = u.id
    WHERE s.expires_at > NOW()
    ORDER BY s.created_at DESC
");

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
        body {
            background: #000;
            color: white;
            font-family: Arial, sans-serif;
        }

        /* Status Section */
        .status-section {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 10px;
            border-bottom: 1px solid #333;
        }

        .status {
            text-align: center;
            color: white;
            flex: 0 0 auto;
        }

        .status img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            padding: 3px;
            border: 2px solid transparent;
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        }

        .status p {
            font-size: 12px;
            margin-top: 5px;
            max-width: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Popup Overlay */
        .popup {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            position: relative;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: fadeIn 0.3s ease-in-out;
        }

        .popup-content img {
            max-width: 100%;
            border-radius: 10px;
        }

        .close-btn {
            position: absolute;
            right: 15px;
            top: 5px;
            font-size: 25px;
            cursor: pointer;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Post Section */
        .post {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            width: 350px;
            background: #111;
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
    <a href="profile_pages/profile.php">Profile</a>
    <a href="logout.php">Logout</a>

    <!-- Status Section -->
    <div class="status-section">
        <?php while ($status = $statuses->fetch_assoc()): ?>
            <div class="status"
                onclick="openStatus('<?php echo htmlspecialchars($status['image']); ?>', '<?php echo htmlspecialchars($status['username']); ?>')">
                <img src="img/profile_img/<?php echo htmlspecialchars($status['profile_picture']); ?>" alt="Profile">
                <p><?php echo htmlspecialchars($status['username']); ?></p>
            </div>
        <?php endwhile; ?>
    </div>


    <!-- Popup -->
    <div id="statusPopup" class="popup" onclick="closeStatus(event)">
        <div class="popup-content">
            <span class="close-btn" onclick="closeStatus(event)">&times;</span>
            <img id="popupImage" src="" alt="Status Image">
            <p id="popupUsername"></p>
        </div>
    </div>


    <hr>

    <?php while ($row = $result->fetch_assoc()): ?>
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

            <button class="like-btn" data-post-id="<?php echo $row['id']; ?>">❤️ Like</button>
            <span id="like-count-<?php echo $row['id']; ?>"><?php echo $row['like_count']; ?></span>

            <form class="comment-form" data-post-id="<?php echo $row['id']; ?>">
                <input type="text" name="comment" placeholder="Add a comment..." required>
                <button type="submit">Post</button>
            </form>

            <div>
                Comments (<?php echo $row['comment_count']; ?>)
            </div>

            <div class="comments" id="comments-<?php echo $row['id']; ?>">
                <?php
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
    <?php endwhile; ?>

    <script>
        function openStatus(statusFile, username) {
            document.getElementById("popupImage").src = "img/status_img/" + statusFile;
            document.getElementById("popupUsername").innerText = username;
            document.getElementById("statusPopup").style.display = "flex";
        }


        function closeStatus(event) {
            if (event.target.classList.contains('popup') || event.target.classList.contains('close-btn')) {
                document.getElementById("statusPopup").style.display = "none";
            }
        }
    </script>


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