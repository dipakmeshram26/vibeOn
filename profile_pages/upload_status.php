<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit;
}

if (!isset($_FILES['status_image'])) {
    echo "No file uploaded.";
    exit;
}

$file = $_FILES['status_image'];

// basic checks
$allowed_types = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/quicktime'];
$max_size = 20 * 1024 * 1024; // 20 MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    die("Upload error code: " . $file['error']);
}
if (!in_array($file['type'], $allowed_types)) {
    die("File type not allowed. Use images or short mp4/quicktime videos.");
}
if ($file['size'] > $max_size) {
    die("File is too large. Max 20 MB.");
}

// ensure folder exists
$target_dir = __DIR__ . '/../img/status_img/';
if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

// save file
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$basename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$target_path = $target_dir . $basename;

if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    die("Failed to move uploaded file.");
}

// set expiry (24 hours from now)
$expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

// insert into DB (table name: statuses)
$stmt = $conn->prepare("INSERT INTO statuses (user_id, image, created_at, expires_at) VALUES (?, ?, NOW(), ?)");
$stmt->bind_param("iss", $user_id, $basename, $expires_at);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    header("Location: profile.php?status=uploaded");
    exit;
} else {
    echo "DB insert error: " . $conn->error;
}
