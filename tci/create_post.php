<?php
// create_post.php
// Handles post creation (server-side). Accepts form or AJAX POST.
// Validates user, CSRF, file uploads, and stores DB entries.
//
// Notes:
// - Only allow images and selected video mime types.
// - Saves files to uploads/ with unique names.
// - Returns JSON for AJAX requests, or redirects on normal POST.

require_once 'db.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Support both form and AJAX
$isAjax = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRF check (if present)
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $message = 'Invalid CSRF token.';
    if ($isAjax) { echo json_encode(['error' => $message]); exit; }
    die($message);
}

// Pull fields
$title = trim($_POST['title'] ?? null);
$content_text = trim($_POST['content_text'] ?? null);
$price = isset($_POST['price']) ? trim($_POST['price']) : null;
$contact_info = isset($_POST['contact_info']) ? trim($_POST['contact_info']) : null;

// Simple server-side sanitation
$title = htmlspecialchars($title);
$content_text = htmlspecialchars($content_text);
$contact_info = htmlspecialchars($contact_info);

// Insert post
$stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content_text, price, contact_info) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$user_id, $title, $content_text, $price ?: null, $contact_info ?: null]);
$post_id = $pdo->lastInsertId();

// Handle uploads
$allowedImageMime = ['image/jpeg','image/png','image/gif','image/webp'];
$allowedVideoMime = ['video/mp4','video/webm','video/quicktime']; // extend as needed
$uploadDir = __DIR__ . '/uploads/';
@mkdir($uploadDir, 0755, true);

if (!empty($_FILES['media_files'])) {
    $files = reindex_files_array($_FILES['media_files']);
    $count = 0;
    foreach ($files as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) continue;
        if ($count >= 6) break; // limit
        // Validate size (e.g., max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) continue;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        // Validate mime and extension roughly
        if (!in_array($mime, array_merge($allowedImageMime, $allowedVideoMime))) continue;
        // Generate unique name
        $safeBase = bin2hex(random_bytes(8));
        $newName = $safeBase . '.' . ($ext ?: get_extension_from_mime($mime));
        $destination = $uploadDir . $newName;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Save to DB
            $stmt = $pdo->prepare("INSERT INTO media (post_id, file_name, file_type, file_size, mime_type) VALUES (?, ?, ?, ?, ?)");
            $type = in_array($mime, $allowedImageMime) ? 'image' : 'video';
            $stmt->execute([$post_id, $newName, $type, $file['size'], $mime]);
            $count++;
        }
    }
}

// For non-AJAX normal form submit, redirect back to home
if (!$isAjax) {
    header('Location: home.php');
    exit;
}

// Otherwise respond JSON with newly created post id
echo json_encode(['success' => true, 'post_id' => (int)$post_id]);
exit;

/* Helpers */

// Reindex PHP multiple file arrays to easier structure
function reindex_files_array($files) {
    $out = [];
    $count = count($files['name']);
    for ($i=0;$i<$count;$i++) {
        $out[] = [
            'name'=>$files['name'][$i],
            'type'=>$files['type'][$i],
            'tmp_name'=>$files['tmp_name'][$i],
            'error'=>$files['error'][$i],
            'size'=>$files['size'][$i]
        ];
    }
    return $out;
}

// Best-effort mime -> extension fallback
function get_extension_from_mime($mime) {
    $map = [
        'image/jpeg'=>'jpg',
        'image/png'=>'png',
        'image/gif'=>'gif',
        'image/webp'=>'webp',
        'video/mp4'=>'mp4',
        'video/webm'=>'webm',
        'video/quicktime'=>'mov'
    ];
    return $map[$mime] ?? 'dat';
}
?>
