<?php
// create_post_page.php
// Full page to create a post with advanced options and accessibility.

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
  <title>Create Post — TCI Social</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="page-bg">
    <div class="container">
      <header class="small-header">
        <h1 class="pixel-title">Create Post</h1>
        <nav><a class="btn" href="home.php">Back to feed</a></nav>
      </header>

      <main class="card form-card" aria-labelledby="create-post-heading">
        <h2 id="create-post-heading">New post</h2>

        <form id="create-post-form" method="post" enctype="multipart/form-data" action="create_post.php">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

          <label for="title">Title</label>
          <input id="title" name="title" type="text" placeholder="Short title" aria-label="Post title">

          <label for="content_text">Description</label>
          <textarea id="content_text" name="content_text" rows="6" placeholder="Describe your idea" aria-label="Post description"></textarea>

          <?php if ($user['user_type'] === 'innovator'): ?>
            <label for="price">Price (optional)</label>
            <input id="price" name="price" inputmode="decimal" placeholder="e.g., 49.99" aria-label="Price">

            <label for="contact_info">Contact info (will be displayed with post)</label>
            <input id="contact_info" name="contact_info" placeholder="Email, phone, or social links" aria-label="Contact info">
          <?php endif; ?>

          <label for="media_files">Attach media (images/videos). Max files: 6</label>
          <input id="media_files" name="media_files[]" type="file" accept="image/*,video/*" multiple aria-label="Attach media files">

          <div class="form-actions">
            <button class="btn neon" type="submit">Create post</button>
            <a class="btn" href="home.php">Cancel</a>
          </div>
        </form>
      </main>
    </div>
  </div>

  <script src="script.js" defer></script>
</body>
</html>
