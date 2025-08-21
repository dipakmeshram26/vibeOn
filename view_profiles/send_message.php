<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) exit("Not logged in");

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$content = trim($_POST['message']);  // "message" form से aa raha hai

if ($content != "") {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $content);
    $stmt->execute();
}
?>
