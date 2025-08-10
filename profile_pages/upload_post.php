<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Post</title>
</head>
<body>
    <h2>Upload a New Post</h2>
    <form action="save_post.php" method="POST" enctype="multipart/form-data">
        <label>Select Image:</label><br>
        <input type="file" name="image" required><br><br>

        <label>Caption:</label><br>
        <textarea name="caption" placeholder="Write something..."></textarea><br><br>

        <button type="submit">Post</button>
    </form>
</body>
</html>
