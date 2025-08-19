<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];
$chat_user_id = $_GET['user'];

$stmt = $conn->prepare("SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC");
$stmt->bind_param("iiii", $user_id, $chat_user_id, $chat_user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['sender_id'] == $user_id) {
        echo "<div style='text-align:right; color:blue;'>You: " . htmlspecialchars($row['message']) . "</div>";
    } else {
        echo "<div style='text-align:left; color:green;'>Friend: " . htmlspecialchars($row['message']) . "</div>";
    }
}
?>
