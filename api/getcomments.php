<?php
/**
 * Get Comments API
 * Returns formatted HTML for comments section
 */

require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserId = $_SESSION['user_id'] ?? null;

$pasteId = $_GET['paste_id'] ?? '';

if (empty($pasteId)) {
    echo '<div class="alert alert-danger">Paste ID required</div>';
    exit;
}

try {
    // Get comments for this paste
    $comments = getComments($pasteId);
    
    if (empty($comments)) {
        echo '<div class="text-center py-5 text-muted">
                <i class="fas fa-comment-slash fa-3x mb-3 opacity-50"></i>
                <p>No comments yet. Be the first to comment!</p>
              </div>';
    } else {
        foreach ($comments as $comment) {
            $replies = getCommentReplies($comment['id']);
            $avatarFile = $comment['profile_image'];
            $avatarPath = __DIR__ . '/../uploads/avatars/' . $avatarFile;
            if ($avatarFile && file_exists($avatarPath)) {
                $avatarUrl = '/uploads/avatars/' . $avatarFile;
            } else {
                $avatarUrl = '/img/default-avatar.svg';
            }
            $username = $comment['username'] ?: 'Anonymous';
            if ($comment['username']) {
                $usernameLink = '<a href="/profile/' . urlencode($username) . '">' . htmlspecialchars($username) . '</a>';
            } else {
                $usernameLink = htmlspecialchars($username);
            }
            $formattedDate = date('M j, Y \a\t g:i A', $comment['created_at']);
            
            echo '<div class="comment-item mb-4 p-3 border rounded" data-comment-id="' . $comment['id'] . '">
                    <div class="d-flex">
                        <div class="avatar-container me-3">
                            <img src="' . htmlspecialchars($avatarUrl) . '" alt="Avatar" class="rounded-circle" width="40" height="40">
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                <strong class="me-2">' . $usernameLink . '</strong>
                                <small class="text-muted">' . $formattedDate . '</small>
                            </div>
                            <p class="mb-2">' . nl2br(htmlspecialchars($comment['content'])) . '</p>
                            <div class="comment-actions">
                                <button class="btn btn-link btn-sm text-muted p-0 me-3" onclick="toggleReplyForm(' . $comment['id'] . ')">
                                    <i class="fas fa-reply me-1"></i>Reply
                                </button>';
            if ($currentUserId && $comment['user_id'] == $currentUserId) {
                echo '<button class="btn btn-link btn-sm text-danger p-0 delete-comment-btn" onclick="deleteComment(' . $comment['id'] . ')" title="Delete comment">'
                     . '<i class="fas fa-trash-alt"></i></button>';
            }
            echo '</div>';
                            
                            <div id="reply-form-' . $comment['id'] . '" class="reply-form mt-3" style="display: none;">
                                <div class="mb-2">
                                    <textarea id="reply-content-' . $comment['id'] . '" class="form-control form-control-sm" rows="2" placeholder="Write a reply..." required></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary btn-sm" id="reply-submit-' . $comment['id'] . '" onclick="submitReplyDirect(' . $comment['id'] . ')">
                                        <i class="fas fa-reply me-1"></i>Reply
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="hideReplyForm(' . $comment['id'] . ')">Cancel</button>
                                </div>
                            </div>';
                            
            // Show replies with collapsible section
            if (!empty($replies)) {
                $replyCount = count($replies);
                echo '<div class="replies-section mt-3">
                        <button class="btn btn-link btn-sm text-muted p-0 d-flex align-items-center" 
                                onclick="toggleReplies(' . $comment['id'] . ')" 
                                id="replies-toggle-' . $comment['id'] . '">
                            <i class="fas fa-chevron-right me-1 transition-transform" id="replies-icon-' . $comment['id'] . '"></i>
                            <span>' . $replyCount . ' ' . ($replyCount == 1 ? 'reply' : 'replies') . '</span>
                        </button>
                        
                        <div class="replies ms-4 mt-2 border-start ps-3" id="replies-content-' . $comment['id'] . '" style="display: none;">';
                
                foreach ($replies as $reply) {
                    $replyAvatarFile = $reply['profile_image'];
                    $replyAvatarPath = __DIR__ . '/../uploads/avatars/' . $replyAvatarFile;
                    if ($replyAvatarFile && file_exists($replyAvatarPath)) {
                        $replyAvatarUrl = '/uploads/avatars/' . $replyAvatarFile;
                    } else {
                        $replyAvatarUrl = '/img/default-avatar.svg';
                    }
                    $replyUsername = $reply['username'] ?: 'Anonymous';
                    if ($reply['username']) {
                        $replyUsernameLink = '<a href="/profile/' . urlencode($replyUsername) . '">' . htmlspecialchars($replyUsername) . '</a>';
                    } else {
                        $replyUsernameLink = htmlspecialchars($replyUsername);
                    }
                    $replyFormattedDate = date('M j, Y \a\t g:i A', $reply['created_at']);
                    
                    echo '<div class="reply-item mb-3 p-2 bg-body-secondary rounded">
                            <div class="d-flex">
                                <div class="avatar-container me-2">
                                    <img src="' . htmlspecialchars($replyAvatarUrl) . '" alt="Avatar" class="rounded-circle" width="32" height="32">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <strong class="me-2">' . $replyUsernameLink . '</strong>
                                        <small class="text-muted">' . $replyFormattedDate . '</small>
                                    </div>
                                    <p class="mb-0 small">' . nl2br(htmlspecialchars($reply['content'])) . '</p>
                                </div>
                            </div>
                          </div>';
                }
                echo '    </div>
                      </div>';
            }
            
            echo '</div>
                  </div>
                </div>';
        }
    }
    
} catch (Exception $e) {
    error_log("Get comments error: " . $e->getMessage());
    echo '<div class="alert alert-danger">Error loading comments: ' . htmlspecialchars($e->getMessage()) . '</div>';}?>
