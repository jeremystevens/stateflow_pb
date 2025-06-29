<?php
/**
 * Get Comments API
 * Returns formatted HTML for comments section
 */

require_once __DIR__ . '/../includes/db.php';

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
            $avatarUrl = $comment['profile_image'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM5Y2E2ZjciLz4KPHN2ZyB4PSIxMCIgeT0iMTAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDIwIDIwIiBmaWxsPSIjZmZmIj4KPHBhdGggZD0iTTEwIDEwYy0xIDAtMS41LS41LTEuNS0xLjVzLjUtMS41IDEuNS0xLjUgMS41LjUgMS41IDEuNS0uNSAxLjUtMS41IDEuNXptNSAwYy40NSAwIDEuMi0uNSAxLjItMS41cy0uNS0xLjUtMS4yLTEuNS0xLjIuNS0xLjIgMS41LjUgMS41IDEuMiAxLjV6Ii8+Cjwvc3ZnPgo8L3N2Zz4K';
            $username = $comment['username'] ?: 'Anonymous';
            $formattedDate = date('M j, Y \a\t g:i A', $comment['created_at']);
            
            echo '<div class="comment-item mb-4 p-3 border rounded" data-comment-id="' . $comment['id'] . '">
                    <div class="d-flex">
                        <div class="avatar-container me-3">
                            <img src="' . htmlspecialchars($avatarUrl) . '" alt="Avatar" class="rounded-circle" width="40" height="40">
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                <strong class="me-2">' . htmlspecialchars($username) . '</strong>
                                <small class="text-muted">' . $formattedDate . '</small>
                            </div>
                            <p class="mb-2">' . nl2br(htmlspecialchars($comment['content'])) . '</p>
                            <div class="comment-actions">
                                <button class="btn btn-link btn-sm text-muted p-0 me-3" onclick="toggleReplyForm(' . $comment['id'] . ')">
                                    <i class="fas fa-reply me-1"></i>Reply
                                </button>
                            </div>
                            
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
                    $replyAvatarUrl = $reply['profile_image'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiM5Y2E2ZjciLz4KPHN2ZyB4PSI2IiB5PSI2IiB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0iI2ZmZiI+CjxwYXRoIGQ9Ik0xMCAxMGMtMSAwLTEuNS0uNS0xLjUtMS41cy41LTEuNSAxLjUtMS41IDEuNS41IDEuNSAxLjUtLjUgMS41LTEuNSAxLjV6bTUgMGMuNDUgMCAxLjItLjUgMS4yLTEuNXMtLjUtMS41LTEuMi0xLjUtMS4yLjUtMS4yIDEuNS41IDEuNSAxLjIgMS41eiIvPgo8L3N2Zz4KPC9zdmc+Cg==';
                    $replyUsername = $reply['username'] ?: 'Anonymous';
                    $replyFormattedDate = date('M j, Y \a\t g:i A', $reply['created_at']);
                    
                    echo '<div class="reply-item mb-3 p-2 bg-body-secondary rounded">
                            <div class="d-flex">
                                <div class="avatar-container me-2">
                                    <img src="' . htmlspecialchars($replyAvatarUrl) . '" alt="Avatar" class="rounded-circle" width="32" height="32">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <strong class="me-2">' . htmlspecialchars($replyUsername) . '</strong>
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
    echo '<div class="alert alert-danger">Error loading comments: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>