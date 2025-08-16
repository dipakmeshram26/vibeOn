<?php
session_start();
include '../db.php';

header('Content-Type: text/plain'); // ensure plain response

if (!isset($_SESSION['user_id'])) {
    exit("not_logged_in");
}

$current_user = $_SESSION['user_id'];
$receiver_id = intval($_POST['id']);

// Prevent self-follow
if ($current_user == $receiver_id) {
    exit("invalid_request");
}

// Check if already requested
$check = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
$check->bind_param("ii", $current_user, $receiver_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    exit("already_requested");
}

// Default = pending request (for private accounts)
$status = "pending";

$stmt = $conn->prepare("INSERT INTO follows (follower_id, following_id, status, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $current_user, $receiver_id, $status);

if ($stmt->execute()) {
    exit("requested");
} else {
    exit("error");
}
