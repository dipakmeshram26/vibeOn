<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) exit("Not logged in");

$current_user = $_SESSION['user_id'];

// Latest chat users list
$stmt = $conn->prepare("
    SELECT u.id, u.username, MAX(m.created_at) as last_msg_time
    FROM messages m
    JOIN users u 
      ON (CASE 
            WHEN m.sender_id = ? THEN m.receiver_id = u.id
            ELSE m.sender_id = u.id
          END)
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY u.id, u.username
    ORDER BY last_msg_time DESC
");
$stmt->bind_param("iii", $current_user, $current_user, $current_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>Chats</h2>
<ul>
<?php while ($row = $result->fetch_assoc()) { ?>
    <li>
        <a href="chat.php?user_id=<?= $row['id'] ?>">
            <?= htmlspecialchars($row['username']) ?>
        </a>
    </li>
<?php } ?>
</ul>
