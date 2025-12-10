<?php
// login.php
// Login form and processing

require_once 'db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF (example)
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request (CSRF).';
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email.';
        }

        if (empty($errors)) {
            // Fetch user
            $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session id to prevent fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                // Redirect
                header('Location: home.php');
                exit;
            } else {
                $errors[] = 'Incorrect email or password.';
            }
        }
    }
}

$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Login — TCI Social</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="page-bg">
    <div class="container" role="main">
      <header class="small-header">
        <h1 class="pixel-title">TCI Social — Login</h1>
      </header>

      <main class="card form-card" aria-labelledby="login-heading">
        <h2 id="login-heading">Log in</h2>

        <?php if (!empty($errors)): ?>
          <div class="alert" role="alert" aria-live="assertive">
            <?php foreach ($errors as $err) echo '<div>'.htmlspecialchars($err).'</div>'; ?>
          </div>
        <?php endif; ?>

        <form id="login-form" method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

          <label for="email">Email</label>
          <input id="email" name="email" type="email" required aria-required="true" aria-label="Email address" placeholder="you@example.com" />

          <label for="password">Password</label>
          <div class="password-row">
            <input id="password" name="password" type="password" required aria-required="true" aria-label="Password" placeholder="Enter your password" />
            <button type="button" class="btn-icon password-toggle" aria-pressed="false" aria-label="Show password" title="Show password">👁️</button>
          </div>
          <small class="microcopy">Click the eye icon to preview password.</small>

          <div class="form-actions">
            <button class="btn neon" type="submit">Log in</button>
            <a href="register.php" class="btn">Create account</a>
          </div>
        </form>
      </main>
    </div>
  </div>

  <script src="script.js" defer></script>
</body>
</html>
