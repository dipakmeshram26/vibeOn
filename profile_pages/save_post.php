<?php
session_start();
include 'db.php'; // DB connection

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$caption = $_POST['caption'];

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $target_dir = "../img/posts/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_name = time() . "_" . basename($_FILES['image']['name']);
    $target_file = $target_dir . $image_name;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, image, caption) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $image_name, $caption);
        $stmt->execute();
        $stmt->close();

        echo "Post uploaded successfully! <a href='profile.php'>Go Home</a>";
    } else {
        echo "Error uploading file.";
    }
} else {
    echo "No file uploaded.";
}
