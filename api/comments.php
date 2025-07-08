<?php
/**
 * Comments API Endpoint
 * Handles AJAX requests for comment operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/achievements.php';
loadAchievementsFromCSV(__DIR__ . '/../database/achievements.csv');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Also check for form data
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? '';
$pasteId = $input['paste_id'] ?? '';

if (empty($pasteId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paste ID is required']);
    exit;
}

try {
    switch ($action) {
        case 'add_comment':
            $content = trim($input['content'] ?? '');
            
            if (empty($content)) {
                http_response_code(400);
                echo json_encode(['error' => 'Comment content cannot be empty']);
                exit;
            }
            
            // Add the comment
            $userId = $_SESSION['user_id'] ?? null;
            $commentId = addComment($pasteId, $content, $userId);
            
            if ($commentId) {
                if ($userId) {
                    updateAchievementProgress($userId, 'Commenter');
                }
                // Get the newly created comment with formatted data
                $db = getDatabase();
                $stmt = $db->prepare("
                    SELECT c.*, u.username, u.profile_image
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.id = ?
                ");
                $stmt->execute([$commentId]);
                $comment = $stmt->fetch();
                
                // Format the comment for frontend
                $formattedComment = [
                    'id' => $comment['id'],
                    'content' => $comment['content'],
                    'username' => $comment['username'] ?: 'Anonymous',
                    'profile_image' => $comment['profile_image'] ? '/uploads/avatars/' . $comment['profile_image'] : '/img/default-avatar.svg',
                    'created_at' => $comment['created_at'],
                    'formatted_date' => date('M j, Y \a\t g:i A', $comment['created_at'])
                ];
                
                echo json_encode([
                    'success' => true,
                    'comment' => $formattedComment,
                    'message' => 'Comment added successfully!'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add comment']);
            }
            break;
            
        case 'add_reply':
            $commentId = $input['comment_id'] ?? '';
            $content = trim($input['content'] ?? '');
            
            if (empty($commentId) || empty($content)) {
                http_response_code(400);
                echo json_encode(['error' => 'Comment ID and content are required']);
                exit;
            }
            
            // Add the reply
            $userId = $_SESSION['user_id'] ?? null;
            $replyId = addCommentReply($commentId, $pasteId, $content, $userId);
            
            if ($replyId) {
                if ($userId) {
                    updateAchievementProgress($userId, 'Commenter');
                }
                // Get the newly created reply
                $db = getDatabase();
                $stmt = $db->prepare("
                    SELECT r.*, u.username, u.profile_image
                    FROM comment_replies r
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.id = ?
                ");
                $stmt->execute([$replyId]);
                $reply = $stmt->fetch();
                
                // Format the reply for frontend
                $formattedReply = [
                    'id' => $reply['id'],
                    'content' => $reply['content'],
                    'username' => $reply['username'] ?: 'Anonymous',
                    'profile_image' => $reply['profile_image'] ? '/uploads/avatars/' . $reply['profile_image'] : '/img/default-avatar.svg',
                    'created_at' => $reply['created_at'],
                    'formatted_date' => date('M j, Y \a\t g:i A', $reply['created_at'])
                ];
                
                echo json_encode([
                    'success' => true,
                    'reply' => $formattedReply,
                    'message' => 'Reply added successfully!'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add reply']);
            }
            break;
            
        case 'get_comments':
            // Get all comments for a paste
            $comments = getComments($pasteId);
            $formattedComments = [];
            
            foreach ($comments as $comment) {
                $replies = getCommentReplies($comment['id']);
                $formattedReplies = [];
                
                foreach ($replies as $reply) {
                    $formattedReplies[] = [
                        'id' => $reply['id'],
                        'content' => $reply['content'],
                        'username' => $reply['username'] ?: 'Anonymous',
                        'profile_image' => $reply['profile_image'] ? '/uploads/avatars/' . $reply['profile_image'] : '/img/default-avatar.svg',
                        'created_at' => $reply['created_at'],
                        'formatted_date' => date('M j, Y \a\t g:i A', $reply['created_at'])
                    ];
                }
                
                $formattedComments[] = [
                    'id' => $comment['id'],
                    'content' => $comment['content'],
                    'username' => $comment['username'] ?: 'Anonymous',
                    'profile_image' => $comment['profile_image'] ? '/uploads/avatars/' . $comment['profile_image'] : '/img/default-avatar.svg',
                    'created_at' => $comment['created_at'],
                    'formatted_date' => date('M j, Y \a\t g:i A', $comment['created_at']),
                    'replies' => $formattedReplies
                ];
            }
            
            echo json_encode([
                'success' => true,
                'comments' => $formattedComments
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Comments API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}?>