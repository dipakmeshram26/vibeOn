<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
require_once "../db.php";

$current_user = (int)$_SESSION['user_id'];
$other_user   = (int)($_POST['other_user_id'] ?? 0);

if ($other_user > 0) {
    // Pehle messages delete
    $stmt = $conn->prepare("DELETE FROM messages 
                            WHERE (sender_id=? AND receiver_id=?) 
                               OR (sender_id=? AND receiver_id=?)");
    $stmt->bind_param("iiii", $current_user, $other_user, $other_user, $current_user);
    $stmt->execute();

    // Fir chat list se remove karne ke liye (agar chat list table ya relation hai)
    // Example: contacts table
    $stmt2 = $conn->prepare("DELETE FROM contacts WHERE (user_id=? AND contact_id=?) OR (user_id=? AND contact_id=?)");
    $stmt2->bind_param("iiii", $current_user, $other_user, $other_user, $current_user);
    $stmt2->execute();

    echo json_encode(['ok'=>true]);
} else {
    echo json_encode(['ok'=>false,'error'=>'Invalid user']);
}
