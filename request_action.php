<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo 'Not logged in';
    exit;
}

$user_id = $_SESSION['user_id'];
$request_id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$request_id || !$action) {
    http_response_code(400);
    echo 'Invalid request';
    exit;
}

// Fetch the request first
$stmt = $conn->prepare("SELECT * FROM follows WHERE id = ? AND following_id = ?");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    http_response_code(404);
    echo 'Request not found';
    exit;
}

if ($action === 'accept') {
    // Update status
    $stmt = $conn->prepare("UPDATE follows SET status='accepted' WHERE id = ? AND following_id = ?");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Insert notification
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
    $message = "Your friend request has been accepted";
    $stmt->bind_param("is", $request['follower_id'], $message);
    $stmt->execute();
    $stmt->close();

} elseif ($action === 'reject') {
    // Delete request
    $stmt = $conn->prepare("DELETE FROM follows WHERE id = ? AND following_id = ?");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    http_response_code(400);
    echo 'Invalid action';
    exit;
}

echo 'success';
?>
