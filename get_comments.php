<?php
include 'db.php';

if (isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);

    $result = $conn->query("
        SELECT c.comment, u.username 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = $post_id
        ORDER BY c.created_at DESC
    ");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<p><b>" . htmlspecialchars($row['username']) . ":</b> " . htmlspecialchars($row['comment']) . "</p>";
        }
    } else {
        echo "<p>No comments yet.</p>";
    }
}
?>
