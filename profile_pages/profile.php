<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

require_once __DIR__ . '/../db.php';

$user_id = $_SESSION['user_id'];

// Check if profile is private and viewer is not following
$stmt = $conn->prepare("SELECT is_private FROM users WHERE id=?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($is_private);
$stmt->fetch();
$stmt->close();

$can_view = true;

if ($is_private) {
  $stmt = $conn->prepare("SELECT status FROM follows WHERE follower_id=? AND following_id=?");
  $stmt->bind_param("ii", $_SESSION['user_id'], $profile_id);
  $stmt->execute();
  $stmt->bind_result($status);
  if ($stmt->fetch()) {
    if ($status != 'accepted') {
      $can_view = false;
    }
  } else {
    $can_view = false;
  }
}

if (!$can_view) {
  echo "<p>This account is private. Follow to see their posts.</p>";
  exit;
}


// defaults
$posts_count = 0;
$followers_count = 0;
$following_count = 0;

/* Posts count */
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?")) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $stmt->bind_result($posts_count);
  $stmt->fetch();
  $stmt->close();
}

/* Followers count (kitne log aapko follow kar rahe - sirf accepted) */
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ? AND status = 'accepted'")) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $stmt->bind_result($followers_count);
  $stmt->fetch();
  $stmt->close();
}

/* Following count (aap kitno ko follow kar rahe) */
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?")) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $stmt->bind_result($following_count);
  $stmt->fetch();
  $stmt->close();
}


// Fetch logged-in user data
$stmt = $conn->prepare("SELECT id, full_name, username, profile_picture, is_private, bio FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if already following
$stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
$stmt->bind_param("ii", $logged_in_user, $user_id);
$stmt->execute();
$stmt->store_result();
$is_following = $stmt->num_rows > 0;
$stmt->close();

$posts = [];
if ($stmt = $conn->prepare("SELECT id, image, caption FROM posts WHERE user_id = ? ORDER BY created_at DESC")) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
  }
  $stmt->close();
}

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
  <link rel="stylesheet" href="../sidebar.css">
  <link rel="stylesheet" href="style.css">
  <style>

  </style>
</head>

<body>

  <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <p style="color: green; font-weight: bold;">✅ Profile updated successfully!</p>
  <?php endif; ?>



  <!-- Private/Public info -->
  <p>
    Account Type:
    <?php echo $user['is_private'] ? "🔒 Private" : "🌍 Public"; ?>
  </p>
  <!-- Profile Header -->
  <div class="profile-header">
    <div class="profile-pic">
      <img src="../img/profile_img/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile">
    </div>
    <div class="profile-info">
      <div class="top-line">
        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
        <a href="settings.php" class="settings-icon">⚙</a>
      </div>


      <div class="counts">
        <span><strong><?php echo $posts_count; ?></strong> posts</span>
        <span>
          <a href="followers.php?id=<?php echo $user_id; ?>">
            <strong><?php echo $followers_count; ?></strong> followers
          </a>
        </span>
        <span>
          <a href="following.php?id=<?php echo $user_id; ?>">
            <strong><?php echo $following_count; ?></strong> following
          </a>
        </span>
      </div>

      <div>
        <a href="edit_profile.php" class="btn-edit">Edit Profile</a>
        <a href="upload_post.php"> Create Post</a>
      </div>
    </div>
  </div>
  <div class="wrap">
    <a href="../home.php">&larr; Back to Home</a>
    <div class="profile card">
      <img src="../img/profile_img/<?php echo htmlspecialchars($user['profile_picture'] ?: 'default.png'); ?>"
        alt="Profile">
      <div class="info">
        <div class="name"><?php echo htmlspecialchars($user['full_name']); ?></div>
        <div class="username">@<?php echo htmlspecialchars($user['username']); ?></div>
        <div class="bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></div>
      </div>
    </div>

    <a href="edit_profile.php">edit profile</a>

    <hr>

    <form action="follow_requests.php" method="POST">
      <input type="hidden" name="target_id" value="<?php echo $profile_user_id; ?>">

      <?php if ($is_following): ?>
        <button type="submit" name="unfollow">Unfollow</button>
      <?php else: ?>
        <button type="submit" name="follow">Follow</button>
      <?php endif; ?>
    </form>


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
                    $hours = floor($remaining / 3600);
                    $mins = floor(($remaining % 3600) / 60);
                    echo "Expires in " . ($hours > 0 ? $hours . "h " : "") . $mins . "m";
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

    <!-- Posts section -->
    <div class="posts-section">
      <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post): ?>
          <div class="post-item">
            <img src="../img/posts/<?php echo htmlspecialchars($post['image']); ?>" alt="Post"
              onclick="openPopup(this.src)">


            <!-- <p><?php echo htmlspecialchars($post['caption']); ?></p> -->
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No posts yet.</p>
      <?php endif; ?>
    </div>
    <!-- Popup Modal -->
    <div id="imagePopup" class="popup" onclick="closePopup()">
      <span class="close">&times;</span>
      <img class="popup-content" id="popupImage">
    </div>

  </div>

  <script>
    function openPopup(src) {
      document.getElementById("imagePopup").style.display = "block";
      document.getElementById("popupImage").src = src;
    }

    function closePopup() {
      document.getElementById("imagePopup").style.display = "none";
    }
  </script>
</body>

</html>