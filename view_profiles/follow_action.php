<?php
session_start();
include '../db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$target_id = intval($_POST['id']);
$action = $_POST['action'] ?? '';

if (!$user_id || $user_id == $target_id) {
    exit('error');
}

if ($action === 'follow') {
    $stmt = $conn->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $target_id);
    $stmt->execute();
    echo 'followed';
} elseif ($action === 'unfollow') {
    $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $user_id, $target_id);
    $stmt->execute();
    echo 'unfollowed';
} else {
    echo 'error';
}
