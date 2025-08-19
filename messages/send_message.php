<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'err'=>'unauth']); exit; }
$me = (int)$_SESSION['user_id'];

$to = isset($_POST['to']) ? (int)$_POST['to'] : 0;
$content = trim($_POST['content'] ?? '');

if ($to <= 0 || $content === '') { echo json_encode(['ok'=>false,'err'=>'bad_req']); exit; }

// (Optional) Private account block here if needed…

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $me, $to, $content);
$ok = $stmt->execute();

echo json_encode(['ok'=>$ok]);
