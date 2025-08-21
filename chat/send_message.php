<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
require_once '../db.php';

$sender_id   = (int)$_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message     = trim($_POST['message'] ?? '');

if ($receiver_id <= 0 || $message === '') {
    echo json_encode(['ok'=>false,'error'=>'Bad input']); exit;
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param('iis', $sender_id, $receiver_id, $message);
$ok = $stmt->execute();

echo json_encode([
    'ok'  => $ok,
    'id'  => $ok ? $stmt->insert_id : null
]);
