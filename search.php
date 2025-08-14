<?php
session_start();
include 'db.php';

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo "Please enter a search term.";
    exit;
}

// Search query: username ya id match kare
$stmt = $conn->prepare("
    SELECT id, username, profile_picture 
    FROM users 
    WHERE username LIKE ? OR id LIKE ?
");
$searchTerm = "%$q%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
    <style>
        .user-list { display: flex; flex-direction: column; gap: 10px; max-width: 400px; margin: auto; }
        .user-card { display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 8px; transition: 0.2s; }
        .user-card:hover { background: #f8f8f8; }
        .user-card img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>
    <h2>Search Results for "<?php echo htmlspecialchars($q); ?>"</h2>
    <div class="user-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <a class="user-card" href="view_profiles/view_profile.php?id=<?php echo $row['id']; ?>">
                    <img src="img/profile_img/<?php echo $row['profile_picture'] ?: 'default.png'; ?>" alt="">
                    <span><?php echo htmlspecialchars($row['username']); ?> (ID: <?php echo $row['id']; ?>)</span>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
