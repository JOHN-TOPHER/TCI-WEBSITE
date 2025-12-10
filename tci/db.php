<?php
// db.php
// Database connection using PDO. Place this file in project root.
// Change DB credentials to match your environment.

$DB_HOST = '127.0.0.1';
$DB_NAME = 'tci_social';
$DB_USER = 'root';
$DB_PASS = ''; // <-- set your DB password

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Emulate prepares disabled for proper native prepared statements
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, $options);
} catch (Exception $e) {
    // Don't leak sensitive details in production.
    echo "Database connection failed. Please check your configuration.";
    error_log("DB Connection error: " . $e->getMessage());
    exit;
}

// Helper: start a secure session
function secure_session_start() {
    $session_name = 'tci_session';
    $secure = false; // set to true if using HTTPS
    $httponly = true;
    ini_set('session.use_only_cookies', 1);
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => 'Lax'
    ]);
    session_name($session_name);
    if (session_status() === PHP_SESSION_NONE) session_start();
}

secure_session_start();

// Simple auth check function
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Load current user
function current_user($pdo) {
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare("SELECT id, username, email, profile_pic, bio, user_type FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// CSRF token utility (simple example)
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>
