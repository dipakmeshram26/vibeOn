<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $is_private = isset($_POST['is_private']) ? 1 : 0;

    // Profile picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $file_name = time() . "_" . basename($_FILES['profile_picture']['name']);
        $target = "../img/profile_img/" . $file_name;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target);
    } else {
        $file_name = $user['profile_picture']; // Keep old picture
    }

    // Update query including privacy setting
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, profile_picture = ?, is_private = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $full_name, $username, $email, $file_name, $is_private, $user_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['full_name'] = $full_name;
    $_SESSION['username'] = $username;

    // ✅ redirect with success flag
    header("Location: profile.php?id=$user_id&updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
</head>
<body>

<h2>Edit Profile</h2>
<form method="post" enctype="multipart/form-data">
    <label>Full Name:</label><br>
    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required><br><br>

    <label>Username:</label><br>
    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br><br>

    <label>Profile Picture:</label><br>
    <input type="file" name="profile_picture"><br>
    <small>Current: <img src="../img/profile_img/<?php echo $user['profile_picture'] ?: 'default.png'; ?>" width="50" style="border-radius:50%;"></small><br><br>
<!-- Privacy Toggle -->
    <label>
        <input type="checkbox" name="is_private" value="1" <?php if ($user['is_private']) echo "checked"; ?>>
        Private Account
    </label><br><br>


    <button type="submit">Save Changes</button>
</form>

<a href="profile.php">Back to Profile</a>

</body>
</html>
