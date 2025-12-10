<?php
// post.php
// Single post page (view with full comments). Accepts GET id.

require_once 'db.php';
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$post_id) {
    header('Location: home.php');
    exit;
}

// Fetch post
$stmt = $pdo->prepare("SELECT p.*, u.username, u.profile_pic, u.user_type FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();
if (!$post) {
    echo "Post not found.";
    exit;
}

// Fetch media
$stmt = $pdo->prepare("SELECT * FROM media WHERE post_id = ?");
$stmt->execute([$post_id]);
$media = $stmt->fetchAll();

// Fetch comments
$stmt = $pdo->prepare("SELECT c.*, u.username, u.profile_pic FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

// likes count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$stmt->execute([$post_id]);
$likes = (int)$stmt->fetchColumn();

$userLiked = false;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    $userLiked = ((int)$stmt->fetchColumn()) > 0;
}

$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title><?php echo htmlspecialchars($post['title'] ?: 'Post'); ?> — TCI Social</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="page-bg">
    <div class="container">
      <header class="small-header">
        <h1 class="pixel-title"><?php echo htmlspecialchars($post['title'] ?: 'Post'); ?></h1>
        <nav><a class="btn" href="home.php">Back</a></nav>
      </header>

      <main class="card post-detail" aria-labelledby="post-title">
        <div class="post-header">
          <img src="<?php echo htmlspecialchars($post['profile_pic'] ?? 'uploads/default_avatar.png'); ?>" alt="" class="avatar-small" />
          <div>
            <div class="username"><?php echo htmlspecialchars($post['username']); ?></div>
            <div class="meta"><?php echo htmlspecialchars($post['created_at']); ?> • <?php echo htmlspecialchars(ucfirst($post['user_type'])); ?></div>
          </div>
        </div>

        <article id="post-content" class="post-body">
          <h2 id="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
          <p><?php echo nl2br(htmlspecialchars($post['content_text'])); ?></p>
          <?php if ($post['price']): ?>
            <div class="price">Price: ₱<?php echo htmlspecialchars($post['price']); ?></div>
            <div class="microcopy">Contact: <?php echo htmlspecialchars($post['contact_info']); ?></div>
          <?php endif; ?>

          <div class="media-grid">
            <?php foreach ($media as $m): ?>
              <?php $path = 'uploads/' . htmlspecialchars($m['file_name']); ?>
              <?php if ($m['file_type'] === 'image'): ?>
                <img src="<?php echo $path; ?>" alt="" class="media-item" />
              <?php else: ?>
                <video controls class="media-item"><source src="<?php echo $path; ?>" type="<?php echo htmlspecialchars($m['mime_type']); ?>"></video>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>

          <div class="post-actions">
            <button class="btn like-btn" data-post-id="<?php echo $post_id; ?>" aria-pressed="<?php echo $userLiked ? 'true' : 'false'; ?>">❤ <span class="likes-count"><?php echo $likes; ?></span></button>
            <a class="btn" href="#comments">Comments (<?php echo count($comments); ?>)</a>
          </div>
        </article>

        <section id="comments" class="comments">
          <h3>Comments</h3>
          <?php if (is_logged_in()): ?>
            <form id="comment-form" method="post" action="comment_post.php" data-post-id="<?php echo $post_id; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
              <textarea name="comment_text" rows="2" placeholder="Write a comment" required aria-label="Write a comment"></textarea>
              <div class="form-actions"><button class="btn neon" type="submit">Comment</button></div>
            </form>
          <?php else: ?>
            <p><a href="login.php">Log in</a> to comment.</p>
          <?php endif; ?>

          <div class="comment-list">
            <?php foreach ($comments as $c): ?>
              <div class="comment">
                <img src="<?php echo htmlspecialchars($c['profile_pic'] ?? 'uploads/default_avatar.png'); ?>" alt="" class="avatar-small" />
                <div>
                  <div class="username"><?php echo htmlspecialchars($c['username']); ?></div>
                  <div class="text"><?php echo nl2br(htmlspecialchars($c['comment_text'])); ?></div>
                  <div class="meta"><?php echo htmlspecialchars($c['created_at']); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </main>
    </div>
  </div>

  <script>
    window.TCI_USER = {
      id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
      csrf: <?php echo json_encode($csrf); ?>
    };
  </script>
  <script src="script.js" defer></script>
</body>
</html>
