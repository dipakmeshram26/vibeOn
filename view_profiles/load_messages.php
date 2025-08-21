<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) exit("Not logged in");

$current_user_id = $_SESSION['user_id'];
$receiver_id = $_GET['receiver_id'];

// Fetch messages between both users
$stmt = $conn->prepare("
    SELECT sender_id, content, created_at 
    FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->bind_param("iiii", $current_user_id, $receiver_id, $receiver_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['sender_id'] == $current_user_id) {
        echo "<div style='text-align:right; color:blue;'>" . htmlspecialchars($row['content']) . "</div>";
    } else {
        echo "<div style='text-align:left; color:green;'>" . htmlspecialchars($row['content']) . "</div>";
    }
}
?>
