<?php
// like_post.php
// Toggle like/unlike via AJAX. Expects POST with post_id and csrf_token.

require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
if (!$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post id']);
    exit;
}

// CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'CSRF token invalid']);
    exit;
}

// Check if post exists
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Post not found']);
    exit;
}

// Check if like exists
$stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$stmt->execute([$_SESSION['user_id'], $post_id]);
$exists = $stmt->fetch();

if ($exists) {
    // Remove like
    $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
    $stmt->execute([$exists['id']]);
    echo json_encode(['success' => true, 'liked' => false]);
    exit;
} else {
    // Add like
    $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    try {
        $stmt->execute([$_SESSION['user_id'], $post_id]);
        echo json_encode(['success' => true, 'liked' => true]);
        exit;
    } catch (Exception $e) {
        // Unique constraint may fail in race conditions
        echo json_encode(['success' => true, 'liked' => true]);
        exit;
    }
}
?>
