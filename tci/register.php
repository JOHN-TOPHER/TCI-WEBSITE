<?php
// register.php
// Registration for students and innovators (extra fields for innovators)

require_once 'db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request (CSRF).';
    } else {
        // Basic sanitation
        $username = trim($_POST['username'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $user_type = ($_POST['user_type'] === 'innovator') ? 'innovator' : 'student';

        if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        // Check for duplicates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $count = (int)$stmt->fetchColumn();
        if ($count > 0) $errors[] = 'Email or username already exists.';

        if (empty($errors)) {
            // Hash password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, user_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, $user_type]);
            $user_id = $pdo->lastInsertId();

            // Create empty socials row
            $stmt = $pdo->prepare("INSERT INTO user_socials (user_id) VALUES (?)");
            $stmt->execute([$user_id]);

            $success = true;
            // Auto-login user
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            header('Location: home.php');
            exit;
        }
    }
}

$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Register — TCI Social</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="page-bg">
    <div class="container" role="main">
      <header class="small-header">
        <h1 class="pixel-title">Register</h1>
      </header>

      <main class="card form-card" aria-labelledby="register-heading">
        <h2 id="register-heading">Create an account</h2>

        <?php if (!empty($errors)): ?>
          <div class="alert" role="alert" aria-live="assertive">
            <?php foreach ($errors as $err) echo '<div>'.htmlspecialchars($err).'</div>'; ?>
          </div>
        <?php endif; ?>

        <form id="register-form" method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

          <label for="username">Username</label>
          <input id="username" name="username" type="text" required aria-required="true" placeholder="Choose a username" />

          <label for="email">Email</label>
          <input id="email" name="email" type="email" required aria-required="true" placeholder="you@example.com" />

          <label for="user_type">Account type</label>
          <select id="user_type" name="user_type" aria-label="Account type" required>
            <option value="student">Student</option>
            <option value="innovator">Innovator</option>
          </select>
          <small class="microcopy">Innovators can add price & contact info to posts.</small>

          <label for="password">Password</label>
          <div class="password-row">
            <input id="password" name="password" type="password" required aria-required="true" />
            <button type="button" class="btn-icon password-toggle" aria-pressed="false" aria-label="Show password">👁️</button>
          </div>

          <label for="confirm_password">Confirm password</label>
          <div class="password-row">
            <input id="confirm_password" name="confirm_password" type="password" required aria-required="true" />
            <button type="button" class="btn-icon password-toggle" aria-pressed="false" aria-label="Show confirm password">👁️</button>
          </div>

          <div class="form-actions">
            <button class="btn neon" type="submit">Register</button>
            <a class="btn" href="login.php">Already have an account?</a>
          </div>
        </form>
      </main>
    </div>
  </div>

  <script src="script.js" defer></script>
</body>
</html>
