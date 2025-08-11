<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../db.php';

$user_id = $_SESSION['user_id'];

// Fetch logged-in user data
$stmt = $conn->prepare("SELECT id, full_name, username, profile_picture, bio FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch this user's active statuses (not expired)
$statuses_stmt = $conn->prepare("
    SELECT id, image, created_at, expires_at
    FROM statuses
    WHERE user_id = ? AND (expires_at > NOW() OR expires_at IS NULL)
    ORDER BY created_at DESC
");
$statuses_stmt->bind_param("i", $user_id);
$statuses_stmt->execute();
$statuses = $statuses_stmt->get_result();
$statuses_stmt->close();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php echo htmlspecialchars($user['username']); ?> — Profile</title>
  <style>
    body { font-family: Arial, sans-serif; background:#fafafa; color:#111; padding:20px;}
    .wrap { max-width:900px; margin:0 auto; }
    .profile { display:flex; gap:20px; align-items:center; margin-bottom:20px; }
    .profile img { width:100px; height:100px; border-radius:50%; object-fit:cover; border:2px solid #ddd; }
    .profile .info { line-height:1.2; }
    .profile .info .name { font-size:20px; font-weight:700; }
    .profile .info .username { color:#666; }
    .card { background:#fff; padding:15px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-bottom:20px; }

    /* upload form */
    .upload-form input[type="file"] { display:block; margin-bottom:10px; }
    .upload-form button { background:#3897f0; color:#fff; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; }

    /* status list */
    .statuses-grid { display:flex; gap:12px; flex-wrap:wrap; }
    .status-item { width:110px; text-align:center; }
    .status-thumb {
      width:110px; height:110px; border-radius:12px; overflow:hidden;
      background:#eee; display:block; object-fit:cover; border:2px solid #f09433; 
    }
    .status-meta { font-size:12px; color:#666; margin-top:6px; }
    .small-btn { background:#eee; border:1px solid #ddd; padding:6px 8px; border-radius:6px; cursor:pointer; }
  </style>
</head>
<body>
  <div class="wrap">
    <a href="../home.php">&larr; Back to Home</a>
    <div class="profile card">
      <img src="../img/profile_img/<?php echo htmlspecialchars($user['profile_picture'] ?: 'default.png'); ?>" alt="Profile">
      <div class="info">
        <div class="name"><?php echo htmlspecialchars($user['full_name']); ?></div>
        <div class="username">@<?php echo htmlspecialchars($user['username']); ?></div>
        <div class="bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></div>
      </div>
    </div>

    <!-- Upload status -->
    <div class="card">
      <h3>Add a Status (visible 24 hours)</h3>
      <form class="upload-form" action="upload_status.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="status_image" accept="image/*,video/*" required>
        <div style="font-size:13px; color:#555; margin-bottom:8px;">
          Allowed: images and short videos. Max 20 MB.
        </div>
        <button type="submit">Upload Status</button>
      </form>
    </div>

    <!-- Active statuses -->
    <div class="card">
      <h3>Your Active Statuses</h3>
      <?php if ($statuses->num_rows === 0): ?>
        <p>No active statuses. Upload one above to appear in the home stories.</p>
      <?php else: ?>
        <div class="statuses-grid">
          <?php while ($s = $statuses->fetch_assoc()): ?>
            <div class="status-item">
              <a href="../img/status_img/<?php echo htmlspecialchars($s['image']); ?>" target="_blank">
                <img class="status-thumb" src="../img/status_img/<?php echo htmlspecialchars($s['image']); ?>" alt="status">
              </a>
              <div class="status-meta">
                <?php
                  $expires = $s['expires_at'];
                  if ($expires) {
                    $remaining = strtotime($expires) - time();
                    if ($remaining > 0) {
                      $hours = floor($remaining/3600);
                      $mins = floor(($remaining%3600)/60);
                      echo "Expires in " . ($hours>0 ? $hours . "h " : "") . $mins . "m";
                    } else {
                      echo "Expired";
                    }
                  } else {
                    echo "No expiry set";
                  }
                ?>
              </div>
              <form method="post" action="delete_status.php" style="margin-top:6px;">
                <input type="hidden" name="status_id" value="<?php echo intval($s['id']); ?>">
                <button type="submit" class="small-btn">Delete</button>
              </form>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>
