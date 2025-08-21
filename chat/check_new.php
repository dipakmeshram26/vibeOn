<?php
session_start();
include("../db.php");

if (!isset($_SESSION['user_id'])) {
    die();
}

$user_id = $_SESSION['user_id'];

// unseen messages
$stmt = $conn->prepare("SELECT m.id, m.content, u.username 
                        FROM messages m
                        JOIN users u ON m.sender_id = u.id
                        WHERE m.receiver_id = ? AND m.seen = 0
                        ORDER BY m.created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);

    // mark as seen (so it won't repeat)
    $update = $conn->prepare("UPDATE messages SET seen = 1 WHERE id = ?");
    $update->bind_param("i", $row['id']);
    $update->execute();
} else {
    echo json_encode([]);
}
