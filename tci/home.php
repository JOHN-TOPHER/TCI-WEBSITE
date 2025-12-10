<?php
// home.php
// Main feed page. Needs login.

require_once 'db.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = current_user($pdo);
$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Home — TCI Social</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>
<body>
  <div class="page-bg">
    <div class="container" role="main" aria-live="polite">
      <header class="site-header">
        <h1 class="pixel-title">TCI Social</h1>

        <nav class="nav">
          <a href="home.php" class="nav-item" aria-current="page">Feed</a>
          <a href="profile.php" class="nav-item">Profile</a>
          <a href="create_post_page.php" class="nav-item">New Post</a>
          <a href="logout.php" class="nav-item">Logout</a>
        </nav>

        <div class="profile-mini" aria-hidden="false">
          <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'uploads/default_avatar.png'); ?>" alt="Your avatar" class="avatar-small" />
          <div class="profile-info">
            <div class="username"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="user-type"><?php echo htmlspecialchars(ucfirst($user['user_type'])); ?></div>
          </div>
        </div>
      </header>

      <main class="layout">
        <section class="left-col" aria-labelledby="feed-heading">
          <h2 id="feed-heading" class="sr-only">Feed</h2>

          <!-- New post quick composer -->
          <div class="card composer" role="region" aria-label="Create a new post">
            <form id="quick-post-form" method="post" enctype="multipart/form-data" action="create_post.php" aria-label="Create post form">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="text" name="title" placeholder="Title (optional)" aria-label="Post title">
              <textarea name="content_text" placeholder="Share something... (students see, innovators can include price & contacts)" aria-label="Post content"></textarea>

              <!-- Price & contact fields are shown conditionally client-side if user is innovator -->
              <?php if ($user['user_type'] === 'innovator'): ?>
              <input name="price" type="text" inputmode="decimal" placeholder="Price (optional) e.g., 79.99" aria-label="Price" />
              <input name="contact_info" type="text" placeholder="Contact info (shown with post)" aria-label="Contact info" />
              <?php endif; ?>

              <label for="media" class="file-label">Attach images/videos (max 4)</label>
              <input id="media" name="media_files[]" type="file" accept="image/*,video/*" multiple aria-label="Attach media">

              <div class="form-actions">
                <button class="btn neon" id="post-submit" type="submit">Post</button>
              </div>
            </form>
          </div>

          <!-- Feed container where posts will be loaded via AJAX -->
          <div id="feed" class="feed-list" aria-live="polite" aria-busy="false"></div>
        </section>

        <aside class="right-col" aria-labelledby="profile-heading">
          <h2 id="profile-heading">Your Profile</h2>
          <div class="card profile-card">
            <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'uploads/default_avatar.png'); ?>" alt="Profile picture" class="avatar-large" />
            <h3><?php echo htmlspecialchars($user['username']); ?></h3>
            <p class="microcopy"><?php echo htmlspecialchars($user['bio'] ?? 'Add a bio — tell people what you build.'); ?></p>
            <div class="profile-actions">
              <a class="btn" href="profile.php">Edit profile</a>
              <a class="btn" href="create_post_page.php">New post</a>
            </div>
          </div>

          <div class="card">
            <h4>Tips</h4>
            <ul>
              <li>Students: Learn and comment kindly.</li>
              <li>Innovators: Be clear about contact & price.</li>
            </ul>
          </div>
        </aside>
      </main>

      <footer class="site-footer">
        <small>Auto-refresh uses AJAX polling for this demo.</small>
      </footer>
    </div>
  </div>

  <script>
    // Small inline: expose user info to client script for conditional UI
    window.TCI_USER = {
      id: <?php echo json_encode($user['id']); ?>,
      username: <?php echo json_encode($user['username']); ?>,
      type: <?php echo json_encode($user['user_type']); ?>,
      csrf: <?php echo json_encode($csrf); ?>
    };
  </script>
  <script src="script.js" defer></script>
</body>
</html>
