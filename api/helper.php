<?php
/**
 * Comment Helper API
 * Simple API for comment operations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/achievements.php';
loadAchievementsFromCSV(__DIR__ . '/../database/achievements.csv');

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
            $userId = $_SESSION['user_id'] ?? null;
            $commentId = addComment($pasteId, $content, $userId);
            
            if ($commentId) {
                if ($userId) {
                    updateAchievementProgress($userId, 'Commenter');
                }
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
            $userId = $_SESSION['user_id'] ?? null;
            $replyId = addCommentReply($commentId, $pasteId, $content, $userId);
            
            if ($replyId) {
                if ($userId) {
                    updateAchievementProgress($userId, 'Commenter');
                }
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
}?>