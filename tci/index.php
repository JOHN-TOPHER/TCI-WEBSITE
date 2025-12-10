<?php
// index.php
// Landing page. If user logged in, redirect to home. Else show intro and links.

require_once 'db.php';

if (is_logged_in()) {
    header('Location: home.php');
    exit;
}

$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>TCI Social — Welcome</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>
<body>
  <div class="page-bg">
    <div class="container" role="main" aria-labelledby="site-title">
      <header class="site-header">
        <h1 id="site-title" class="pixel-title">TCI Social</h1>
        <p class="lead">A place for students and innovators — learn, showcase, sell, and connect.</p>
      </header>

      <main class="home-cards">
        <section class="card" aria-labelledby="get-started">
          <h2 id="get-started">Get started</h2>
          <p>Register as a Student or Innovator. Innovators can list projects with price and contact info.</p>
          <div class="actions">
            <a class="btn neon" href="register.php" role="button">Create Account</a>
            <a class="btn" href="login.php" role="button">Log in</a>
          </div>
        </section>

        <section class="card" aria-labelledby="features">
          <h2 id="features">Features</h2>
          <ul>
            <li>Newsfeed with posts, images & videos</li>
            <li>Like, comment, and save favorite projects</li>
            <li>AJAX-powered interactions & auto refresh</li>
            <li>Accessible UI, pixel title, soft animations</li>
          </ul>
        </section>
      </main>

      <footer class="site-footer">
        <small>Demo site — do not use for production without hardening.</small>
      </footer>
    </div>
  </div>
  <script src="script.js" defer></script>
</body>
</html>
