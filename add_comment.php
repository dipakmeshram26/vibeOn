<?php
session_start();
include 'db.php';

$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id'];
$comment = $conn->real_escape_string($_POST['comment']);

$conn->query("INSERT INTO comments (post_id, user_id, comment) VALUES ($post_id, $user_id, '$comment')");

$user = $conn->query("SELECT username FROM users WHERE id=$user_id")->fetch_assoc();
echo "<p><b>" . htmlspecialchars($user['username']) . ":</b> " . htmlspecialchars($comment) . "</p>";
?>
