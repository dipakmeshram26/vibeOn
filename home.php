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
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="homestyle.css">

</head>

<body>
    <div class="container">
        <!-- LEFT SIDEBAR -->
        <div class="sidebar-left">
            <h2>VibeOn</h2>
            <ul>
                <li> <a href="home.php">🏠 Home</a></li>
                <li>🔍 Search</li>
                <li>✉️ Messages</li>
                <li><a href="profile_pages/profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
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


            <!-- Status Section -->
            <div class="status-section">
                <?php while ($status = $statuses->fetch_assoc()): ?>
                    <div class="status"
                        onclick="openStatus('<?php echo htmlspecialchars($status['image']); ?>', '<?php echo htmlspecialchars($status['username']); ?>')">
                        <img src="img/profile_img/<?php echo htmlspecialchars($status['profile_picture']); ?>"
                            alt="Profile">
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

            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> 👋</h2>
            <!-- <p>You are now logged in as <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></p> -->
            <!-- <a href="profile_pages/profile.php">Profile</a>
            <a href="logout.php">Logout</a> -->




            <?php while ($row = $result->fetch_assoc()): ?>
                <div class='post'>
                    <div class='post-header'>
                        <img src='img/profile_img/<?php echo htmlspecialchars($row['profile_picture']); ?>' width='40'
                            height='40' alt="Profile Picture">
                        <b><?php echo htmlspecialchars($row['username']); ?></b>
                    </div>

                    <!-- Post Image (onclick open modal) -->
                    <img class='post-image' src='img/posts/<?php echo htmlspecialchars($row['image']); ?>' alt="Post Image"
                        onclick="openPostModal(
            'img/posts/<?php echo htmlspecialchars($row['image']); ?>',
            '<?php echo htmlspecialchars($row['username']); ?>',
            '<?php echo !empty($row['caption']) ? htmlspecialchars($row['caption']) : ""; ?>',
            '<?php echo $row['id']; ?>'
         )">

                    <?php if (!empty($row['caption'])): ?>
                        <p><?php echo htmlspecialchars($row['caption']); ?></p>
                    <?php endif; ?>

                    <button class="like-btn" data-post-id="<?php echo $row['id']; ?>">❤️ Like</button>
                    <div id="totle_likes">
                        <span id="like-count-<?php echo $row['id']; ?>"><?php echo $row['like_count']; ?></span>
                    </div>

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

            <!-- Post Modal -->
            <div id="postModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closePostModal()">&times;</span>

                    <div class="modal-left">
                        <img id="modalImage" src="" alt="Post Image">
                    </div>

                    <div class="modal-right">
                        <div class="modal-header">
                            <img src="img/default.png" width="40" class="profile-pic">
                            <b id="modalUser"></b>
                        </div>

                        <p id="modalCaption"></p>

                        <div class="modal-actions">❤️ 👍 💬</div>

                        <div class="modal-comments" id="modalComments"></div>

                        <form class="comment-form">
                            <input type="text" placeholder="Add a comment...">
                            <button type="submit">Post</button>
                        </form>
                    </div>
                </div>
            </div>



        </div>

    </div>


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

    <script>
        function openPostModal(imageUrl, username, caption, postId) {
            document.getElementById("modalImage").src = imageUrl;
            document.getElementById("modalUser").innerText = username;
            document.getElementById("modalCaption").innerText = caption;

            // Load comments dynamically if needed (AJAX call example)
            fetch("get_comments.php?post_id=" + postId)
                .then(res => res.text())
                .then(data => {
                    document.getElementById("modalComments").innerHTML = data;
                });

            document.getElementById("postModal").style.display = "flex";
        }

        function closePostModal() {
            document.getElementById("postModal").style.display = "none";
        }


    </script>


</body>

</html>