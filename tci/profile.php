<?php
// profile.php
// View and edit profile. Allows uploading profile picture and editing socials.

require_once 'db.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = current_user($pdo);
$csrf = generate_csrf_token();
$errors = [];
$success = false;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request (CSRF).';
    } else {
        $bio = trim($_POST['bio'] ?? '');
        // Update basic profile
        $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt->execute([htmlspecialchars($bio), $user['id']]);

        // Update socials
        $phone = trim($_POST['phone'] ?? null);
        $facebook = trim($_POST['facebook'] ?? null);
        $instagram = trim($_POST['instagram'] ?? null);
        $tiktok = trim($_POST['tiktok'] ?? null);
        $github = trim($_POST['github'] ?? null);
        $discord = trim($_POST['discord'] ?? null);

        $stmt = $pdo->prepare("UPDATE user_socials SET phone=?, facebook=?, instagram=?, tiktok=?, github=?, discord=? WHERE user_id=?");
        $stmt->execute([$phone, $facebook, $instagram, $tiktok, $github, $discord, $user['id']]);

        // Handle profile picture upload
        if (!empty($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            if (!in_array($mime, $allowed)) {
                $errors[] = 'Uploaded file is not a supported image.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Image too large (max 5MB).';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $newName = bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = __DIR__ . '/uploads/' . $newName;
                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    $errors[] = 'Failed to move uploaded file.';
                } else {
                    // Update DB
                    $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                    $stmt->execute(['uploads/' . $newName, $user['id']]);
                }
            }
        }

        $success = true;
        // Refresh user data
        $user = current_user($pdo);
    }
}

// Fetch socials
$stmt = $pdo->prepare("SELECT * FROM user_socials WHERE user_id = ?");
$stmt->execute([$user['id']]);
$socials = $stmt->fetch();

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Profile — TCI Social</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="page-bg">
    <div class="container">
      <header class="small-header">
        <h1 class="pixel-title">Your Profile</h1>
      </header>

      <main class="card form-card" aria-labelledby="profile-heading">
        <h2 id="profile-heading">Edit profile</h2>

        <?php if ($success): ?>
          <div class="alert success" role="status">Profile updated successfully.</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert" role="alert">
            <?php foreach ($errors as $err) echo '<div>'.htmlspecialchars($err).'</div>'; ?>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

          <label>Profile picture</label>
          <div class="profile-row">
            <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'uploads/default_avatar.png'); ?>" alt="Profile picture" class="avatar-large" />
            <div>
              <input type="file" name="profile_pic" accept="image/*" aria-label="Upload new profile picture">
              <small class="microcopy">Max 5MB. Safe image types only.</small>
            </div>
          </div>

          <label for="bio">Bio</label>
          <textarea id="bio" name="bio" rows="4" placeholder="Tell people what you build"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>

          <h3>Contact & socials</h3>
          <label for="phone">Phone</label>
          <input id="phone" name="phone" value="<?php echo htmlspecialchars($socials['phone'] ?? ''); ?>">

          <label for="facebook">Facebook</label>
          <input id="facebook" name="facebook" value="<?php echo htmlspecialchars($socials['facebook'] ?? ''); ?>">

          <label for="instagram">Instagram</label>
          <input id="instagram" name="instagram" value="<?php echo htmlspecialchars($socials['instagram'] ?? ''); ?>">

          <label for="tiktok">TikTok</label>
          <input id="tiktok" name="tiktok" value="<?php echo htmlspecialchars($socials['tiktok'] ?? ''); ?>">

          <label for="github">GitHub</label>
          <input id="github" name="github" value="<?php echo htmlspecialchars($socials['github'] ?? ''); ?>">

          <label for="discord">Discord</label>
          <input id="discord" name="discord" value="<?php echo htmlspecialchars($socials['discord'] ?? ''); ?>">

          <div class="form-actions">
            <button class="btn neon" type="submit">Save profile</button>
            <a class="btn" href="home.php">Back</a>
          </div>
        </form>
      </main>
    </div>
  </div>

  <script src="script.js" defer></script>
</body>
</html>
