<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Pending Friend Requests ---
$sql_requests = "
    SELECT f.id, u.username, u.profile_picture
    FROM follows f
    JOIN users u ON f.follower_id = u.id
    WHERE f.following_id = ? AND f.status = 'pending'
";
$stmt = $conn->prepare($sql_requests);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$friend_requests = $result->fetch_all(MYSQLI_ASSOC);

// --- Notifications (accepted requests) ---
$sql_notif = "
    SELECT f.id, u.username, u.profile_picture, f.created_at
    FROM follows f
    JOIN users u ON f.follower_id = u.id
    WHERE f.following_id = ? AND f.status = 'accepted'
    ORDER BY f.created_at DESC
";
$stmt = $conn->prepare($sql_notif);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications_raw = $result->fetch_all(MYSQLI_ASSOC);

// Add custom message for each notification
$notifications = [];
foreach ($notifications_raw as $n) {
    $notifications[] = [
        'id' => $n['id'],
        'username' => $n['username'],
        'profile_picture' => $n['profile_picture'],
        'created_at' => $n['created_at'],
        'message' => "Friend request accepted by " . $n['username']
    ];
}

echo json_encode([
    'friend_requests' => $friend_requests,
    'notifications' => $notifications
]);
?>
