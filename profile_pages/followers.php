<?php
session_start();
include '../db.php';

$user_id = $_GET['id']; // jis profile ka followers dekhna hai

// Fetch followers list
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.profile_picture
    FROM follows f
    JOIN users u ON f.follower_id = u.id
    WHERE f.following_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Followers</title>
    <style>
        .user-list { display: flex; flex-direction: column; gap: 10px; }
        .user-card { display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .user-card img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>
    <h2>Followers</h2>
    <div class="user-list">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="user-card">
                <img src="../uploads/<?php echo $row['profile_picture'] ?: 'default.png'; ?>" alt="">
                <a href="profile.php?id=<?php echo $row['id']; ?>">
                    <?php echo htmlspecialchars($row['username']); ?>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
