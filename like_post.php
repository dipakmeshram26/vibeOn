<?php
session_start();
include 'db.php';

$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id'];

// Check if already liked
$check = $conn->query("SELECT * FROM likes WHERE post_id=$post_id AND user_id=$user_id");
if ($check->num_rows > 0) {
    $conn->query("DELETE FROM likes WHERE post_id=$post_id AND user_id=$user_id");
} else {
    $conn->query("INSERT INTO likes (post_id, user_id) VALUES ($post_id, $user_id)");
}

// Return like count
$count = $conn->query("SELECT COUNT(*) AS c FROM likes WHERE post_id=$post_id")->fetch_assoc();
echo $count['c'];
?>
