<?php
/**
 * Comment Helper API
 * Simple API for comment operations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$pasteId = $_POST['paste_id'] ?? '';

try {
    switch ($action) {
        case 'add_comment':
            $content = trim($_POST['content'] ?? '');
            
            if (empty($pasteId)) {
                echo json_encode(['success' => false, 'error' => 'Paste ID required']);
                exit;
            }
            
            if (empty($content)) {
                echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
                exit;
            }
            
            // Add comment
            $commentId = addComment($pasteId, $content, null);
            
            if ($commentId) {
                echo json_encode(['success' => true, 'message' => 'Comment added successfully!']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add comment']);
            }
            break;
            
        case 'add_reply':
            $commentId = $_POST['comment_id'] ?? '';
            $content = trim($_POST['content'] ?? '');
            
            if (empty($commentId) || empty($content)) {
                echo json_encode(['success' => false, 'error' => 'Comment ID and content are required']);
                exit;
            }
            
            if (empty($pasteId)) {
                echo json_encode(['success' => false, 'error' => 'Paste ID required']);
                exit;
            }
            
            // Add reply
            $replyId = addCommentReply($commentId, $pasteId, $content, null);
            
            if ($replyId) {
                echo json_encode(['success' => true, 'message' => 'Reply added successfully!']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add reply']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Helper API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>