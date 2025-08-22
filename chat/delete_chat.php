<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
require_once "../db.php";

$current_user = (int)$_SESSION['user_id'];
$other_user   = (int)($_POST['other_user_id'] ?? 0);

if ($other_user > 0) {
    $stmt = $conn->prepare("DELETE FROM messages 
                            WHERE (sender_id=? AND receiver_id=?) 
                               OR (sender_id=? AND receiver_id=?)");
    $stmt->bind_param("iiii", $current_user, $other_user, $other_user, $current_user);
    $stmt->execute();
    echo json_encode(['ok'=>true]);
} else {
    echo json_encode(['ok'=>false,'error'=>'Invalid user']);
}
