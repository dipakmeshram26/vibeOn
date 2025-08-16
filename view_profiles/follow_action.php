<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("not_logged_in");
}

$current_user = $_SESSION['user_id'];
$profile_id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$profile_id || !$action) {
    die("invalid");
}

if ($action === "follow") {
    // check if already following
    $check = $conn->prepare("SELECT * FROM follows WHERE follower_id=? AND following_id=?");
    $check->bind_param("ii", $current_user, $profile_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO follows (follower_id, following_id, status) VALUES (?, ?, 'accepted')");
        $stmt->bind_param("ii", $current_user, $profile_id);
        $stmt->execute();
    }
    echo "followed";
}

elseif ($action === "unfollow") {
    $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id=? AND following_id=?");
    $stmt->bind_param("ii", $current_user, $profile_id);
    $stmt->execute();
    echo "unfollowed";
}
