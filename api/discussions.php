<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

try {
    switch ($action) {
        case 'edit_thread':
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            $threadId = $_POST['thread_id'] ?? '';
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $content = trim($_POST['content'] ?? '');
            if (!$threadId || !$title || !$category || !$content) {
                echo json_encode(['success' => false, 'error' => 'Missing data']);
                exit;
            }
            $result = updateDiscussionThread($threadId, $userId, $title, $category, $content);
            echo json_encode(['success' => $result]);
            break;
        case 'edit_post':
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            $postId = $_POST['post_id'] ?? '';
            $content = trim($_POST['content'] ?? '');
            if (!$postId || $content === '') {
                echo json_encode(['success' => false, 'error' => 'Missing data']);
                exit;
            }
            $result = updateDiscussionPost($postId, $userId, $content);
            echo json_encode(['success' => $result]);
            break;
        case 'delete_post':
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            $postId = $_POST['post_id'] ?? '';
            if (!$postId) {
                echo json_encode(['success' => false, 'error' => 'Missing post id']);
                exit;
            }
            $result = deleteDiscussionPost($postId, $userId);
            echo json_encode(['success' => $result]);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('Discussion API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
