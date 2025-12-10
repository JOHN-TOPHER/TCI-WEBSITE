<?php
// fetch_posts.php
// Returns posts in JSON for AJAX feed. Includes media, likes, comments count, and whether current user liked.

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

$limit = 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$user_id = $_SESSION['user_id'] ?? null;

// Basic fetch posts with user info
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_pic, u.user_type
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$out = [];
foreach ($posts as $p) {
    // media
    $stmtm = $pdo->prepare("SELECT * FROM media WHERE post_id = ? ORDER BY id ASC");
    $stmtm->execute([$p['id']]);
    $media = $stmtm->fetchAll();

    // likes count
    $stmtl = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmtl->execute([$p['id']]);
    $likes = (int)$stmtl->fetchColumn();

    // comments count
    $stmtc = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $stmtc->execute([$p['id']]);
    $comments = (int)$stmtc->fetchColumn();

    // whether current user liked
    $user_liked = false;
    if ($user_id) {
        $stmtul = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
        $stmtul->execute([$p['id'], $user_id]);
        $user_liked = ((int)$stmtul->fetchColumn()) > 0;
    }

    $out[] = [
        'post' => $p,
        'media' => $media,
        'likes' => $likes,
        'comments' => $comments,
        'user_liked' => $user_liked
    ];
}

echo json_encode(['data' => $out]);
exit;
?>
