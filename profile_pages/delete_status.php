<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit;
}

$status_id = intval($_POST['status_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($status_id <= 0) {
    header("Location: profile.php");
    exit;
}

// fetch image filename to delete file
$stmt = $conn->prepare("SELECT image FROM statuses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $status_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if ($row) {
    $filename = $row['image'];
    $stmt2 = $conn->prepare("DELETE FROM statuses WHERE id = ? AND user_id = ?");
    $stmt2->bind_param("ii", $status_id, $user_id);
    $stmt2->execute();
    $stmt2->close();

    // delete file if exists
    $path = __DIR__ . '/../img/status_img/' . $filename;
    if (is_file($path)) @unlink($path);
}

header("Location: profile.php");
exit;
