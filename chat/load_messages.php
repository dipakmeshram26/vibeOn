<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']); exit;
}

require_once '../db.php';

$user_id     = (int)$_SESSION['user_id'];
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;

$sql = "
    SELECT m.id, m.content, m.sender_id, m.receiver_id, m.created_at, u.username AS sender_name
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE (m.sender_id = ? AND m.receiver_id = ?)
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.id ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iiii', $user_id, $receiver_id, $receiver_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while ($row = $res->fetch_assoc()) {
    $messages[] = [
        'id'      => (int)$row['id'],
        'text'    => htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8'),
        'sender'  => ($row['sender_id'] == $user_id) ? 'You' : $row['sender_name'],
        'type'    => ($row['sender_id'] == $user_id) ? 'self' : 'friend',
        'created' => $row['created_at'],
    ];
}

echo json_encode($messages);
