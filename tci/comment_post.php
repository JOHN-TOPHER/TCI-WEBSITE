<?php
// comment_post.php
// Add a comment to a post via AJAX POST

require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$payload = $_POST;
if (!isset($payload['post_id']) || !isset($payload['comment_text'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

if (!isset($payload['csrf_token']) || !verify_csrf_token($payload['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'CSRF token invalid']);
    exit;
}

$post_id = (int)$payload['post_id'];
$comment_text = trim($payload['comment_text']);

if ($comment_text === '') {
    echo json_encode(['error' => 'Empty comment']);
    exit;
}

// Server-side ownership & existence check (post exists)
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Post not found']);
    exit;
}

// Insert
$stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $post_id, htmlspecialchars($comment_text)]);
$comment_id = $pdo->lastInsertId();

echo json_encode(['success' => true, 'comment_id' => $comment_id]);
exit;
?>
