<?php
session_start();
include '../db.php';

$follower_id = $_SESSION['user_id'];
$following_id = $_POST['following_id'];

if (isset($_POST['follow'])) {
    $stmt = $conn->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $follower_id, $following_id);
    $stmt->execute();
    $stmt->close();
}
elseif (isset($_POST['unfollow'])) {
    $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $follower_id, $following_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: profile.php?id=" . $following_id);
exit;
?>
