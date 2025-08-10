<?php
// Database connection
include 'db.php';

// Form से data लेना
$full_name = $_POST['full_name'];
$username = $_POST['username'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // password hash
$dob = $_POST['dob'];
$bio = $_POST['bio'];

// Profile picture upload
$profile_picture = "default.png";
if (!empty($_FILES['profile_picture']['name'])) {
    $target_dir = "img/profile_img/"; // Folder path (relative to project root)
    $file_name = time() . "_" . basename($_FILES["profile_picture"]["name"]);
    $target_file = $target_dir . $file_name;

    // Upload file
    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        $profile_picture = $file_name;
    }
}

// Data insert करना
$sql = "INSERT INTO users (full_name, username, email, phone, password, dob, profile_picture, bio)
        VALUES ('$full_name', '$username', '$email', '$phone', '$password', '$dob', '$profile_picture', '$bio')";

if ($conn->query($sql) === TRUE) {
    echo "Signup successful! You can now login.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
