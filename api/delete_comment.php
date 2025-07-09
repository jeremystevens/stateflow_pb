<?php
/**
 * Delete Comment API
 * Allows users to delete their own comments
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$commentId = $_POST['comment_id'] ?? '';
$userId    = $_SESSION['user_id'] ?? null;

if (empty($commentId) || !$userId) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    $result = deleteComment($commentId, $userId);
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unable to delete comment']);
    }
} catch (Exception $e) {
    error_log('Delete comment API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
