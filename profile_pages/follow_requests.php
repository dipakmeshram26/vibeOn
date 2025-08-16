<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle action (accept/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];

    if ($action === 'accept') {
        $stmt = $conn->prepare("UPDATE follows SET status='accepted' WHERE id=? AND following_id=?");
        $stmt->bind_param("ii", $request_id, $user_id);
        $stmt->execute();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("DELETE FROM follows WHERE id=? AND following_id=?");
        $stmt->bind_param("ii", $request_id, $user_id);
        $stmt->execute();
    }
    header("Location: follow_requests.php");
    exit;
}

// Get pending requests
$stmt = $conn->prepare("
    SELECT f.id, u.username, u.profile_picture
    FROM follows f
    JOIN users u ON f.follower_id = u.id
    WHERE f.following_id = ? AND f.status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Follow Requests</title>
</head>
<body>
    <h2>Follow Requests</h2>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div style="margin-bottom: 10px;">
                <img src="../img/profile_img/<?php echo $row['profile_picture'] ?: 'default.png'; ?>" width="40" height="40">
                <?php echo htmlspecialchars($row['username']); ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="action" value="accept">Accept</button>
                    <button type="submit" name="action" value="reject">Reject</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No pending requests.</p>
    <?php endif; ?>
</body>
</html>
