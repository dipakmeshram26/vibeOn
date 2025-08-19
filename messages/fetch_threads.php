<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../db.php';

if (!isset($_SESSION['user_id'])) { http_response_code(403); exit; }
$me = (int)$_SESSION['user_id'];

/*
  Logic:
  - Wo users jinke saath messages exchange hue (sender ya receiver me $me aaye)
  - Un partner ka last message, time, snippet, profile pic
*/
$sql = "
SELECT u.id AS uid, u.username, u.profile_picture,
       m2.content AS last_msg, m2.created_at AS last_time
FROM (
    SELECT 
      CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
      END AS partner_id,
      MAX(m.created_at) AS last_time
    FROM messages m
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY partner_id
) t
JOIN users u ON u.id = t.partner_id
JOIN messages m2 
  ON ((m2.sender_id = ? AND m2.receiver_id = u.id) OR (m2.sender_id = u.id AND m2.receiver_id = ?))
 AND m2.created_at = t.last_time
ORDER BY t.last_time DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $me,$me,$me,$me,$me);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()):
  $avatar = 'img/profile_img/'.($row['profile_picture'] ?: 'default.png');
  $time = date('d M H:i', strtotime($row['last_time']));
  $username = htmlspecialchars($row['username']);
  $snippet = htmlspecialchars($row['last_msg']);
  $uid = (int)$row['uid'];
?>
  <div class="msg-item" 
       data-uid="<?php echo $uid; ?>" 
       data-username="<?php echo $username; ?>"
       data-avatar="<?php echo htmlspecialchars($avatar); ?>">
    <img src="<?php echo htmlspecialchars('../'.$avatar); ?>" alt="">
    <div class="msg-meta">
      <div class="msg-line1">
        <div class="msg-username"><?php echo $username; ?></div>
        <div class="msg-time"><?php echo $time; ?></div>
      </div>
      <div class="msg-snippet"><?php echo $snippet; ?></div>
    </div>
  </div>
<?php endwhile;

if ($res->num_rows === 0) {
  echo '<div style="padding:12px;color:#aaa;">No messages yet.</div>';
}
