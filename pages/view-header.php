<?php
require_once '../includes/db.php';
require_once '../database/init.php';

$pasteId = $_GET['id'] ?? '';
$viewThread = $_GET['view_thread'] ?? '';
$action = $_POST['action'] ?? '';
$paste = null;
$error = '';
$success = '';

// Initialize thread data if viewing a specific thread
$thread = null;
$threadPosts = [];
if (!empty($viewThread)) {
    $thread = getDiscussionThread($viewThread);
    if ($thread && $thread['paste_id'] == $pasteId) {
        $threadPosts = getDiscussionPosts($viewThread);
    } else {
        $error = 'Thread not found or doesn\'t belong to this paste.';
        $viewThread = ''; // Reset to show thread list
    }
}



// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_comment') {
        $content = trim($_POST['content'] ?? '');
        if (!empty($content)) {
            $result = addComment($pasteId, $content, null); // null for anonymous
            if ($result) {
                $success = 'Comment added successfully!';
            } else {
                $error = 'Failed to add comment.';
            }
        } else {
            $error = 'Comment content cannot be empty.';
        }
    } elseif ($action === 'create_discussion_thread') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $username = trim($_POST['username'] ?? 'Anonymous');
        
        if (!empty($title) && !empty($category) && !empty($content)) {
            try {
                $threadId = createDiscussionThread($pasteId, $title, $category, $content, $username);
                if ($threadId) {
                    // Redirect to discussions tab to show the new thread
                    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $pasteId . "#discussions");
                    exit();
                } else {
                    $error = 'Failed to create discussion thread.';
                }
            } catch (Exception $e) {
                $error = 'Error creating discussion thread: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    } elseif ($action === 'add_discussion_post') {
        $threadId = $_POST['thread_id'] ?? '';
        $content = trim($_POST['content'] ?? '');
        $username = trim($_POST['username'] ?? 'Anonymous');
        
        if (!empty($content) && !empty($threadId)) {
            try {
                $postId = addDiscussionPost($threadId, $content, $username);
                if ($postId) {
                    // Redirect back to the thread view
                    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $pasteId . "&thread=" . $threadId . "#discussions");
                    exit();
                } else {
                    $error = 'Failed to add discussion post.';
                }
            } catch (Exception $e) {
                $error = 'Error adding discussion post: ' . $e->getMessage();
            }
        } else {
            $error = 'Post content cannot be empty.';
        }
    }
}

if (empty($pasteId)) {
    $error = 'No paste ID provided.';
} else {
    $paste = getPasteById($pasteId);
    
    if (!$paste) {
        $error = 'Paste not found or has expired.';
    } elseif (isset($paste['burned']) && $paste['burned'] == 1) {
        $error = 'This paste has been burned (deleted after being read) and is no longer available.';
        $paste = null;
    } else {
        // Check if paste has expired
        if ($paste['expire_time'] && $paste['expire_time'] < time()) {
            $error = 'This paste has expired.';
            $paste = null;
        } else {
            // Handle burn after read logic
            $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $creatorToken = $_GET['creator'] ?? null;
            $isCreatorView = false;
            
            // Validate creator token for burn after read pastes
            if ($paste['burn_after_read'] == 1 && $creatorToken && $paste['creator_token']) {
                $isCreatorView = hash_equals($paste['creator_token'], $creatorToken);
            }
            
            if ($paste['burn_after_read'] == 1) {
                if ($isCreatorView) {
                    // Valid creator view - don't count as view, just mark as seen
                    markPasteAsCreatorViewed($pasteId);
                } else {
                    // This is a real view - check if paste should be burned
                    if (shouldBurnPaste($pasteId)) {
                        // Increment view count first, then burn the paste
                        incrementViewCount($pasteId, $userIP);
                        burnPaste($pasteId);
                        
                        // Show burn notification
                        $burnNotification = true;
                    } else {
                        // Not ready to burn yet
                        incrementViewCount($pasteId, $userIP);
                    }
                }
            } else {
                // Normal paste - increment view count
                incrementViewCount($pasteId, $userIP);
            }
            
            // Count lines in the paste content for proper line number generation
            $paste['line_count'] = substr_count($paste['content'], "\n") + 1;
            
            // Get additional data for enhanced view
            try {
                // Get database connection for additional queries
                $db = getDatabase();
                
                // Get versions count
                $stmt = $db->prepare("SELECT COUNT(*) as version_count FROM paste_versions WHERE paste_id = ?");
                $stmt->execute([$pasteId]);
                $versionData = $stmt->fetch();
                $paste['version_count'] = $versionData['version_count'] ?? 0;
                
                // Get forks count  
                $stmt = $db->prepare("SELECT COUNT(*) as fork_count FROM paste_forks WHERE original_paste_id = ?");
                $stmt->execute([$pasteId]);
                $forkData = $stmt->fetch();
                $paste['fork_count'] = $forkData['fork_count'] ?? 0;
                
                // Get comments count
                $stmt = $db->prepare("SELECT COUNT(*) as comment_count FROM comments WHERE paste_id = ? AND is_deleted = 0");
                $stmt->execute([$pasteId]);
                $commentData = $stmt->fetch();
                $paste['comment_count'] = $commentData['comment_count'] ?? 0;
                
                // Get comments for display
                $comments = getComments($pasteId);
                
                // Calculate additional stats
                $paste['line_count'] = substr_count($paste['content'], "\n") + 1;
                $paste['character_count'] = strlen($paste['content']);
                $paste['file_size'] = formatBytes(strlen($paste['content']));
                
            } catch (PDOException $e) {
                error_log("Failed to get paste stats: " . $e->getMessage());
                // Set defaults if queries fail
                $paste['version_count'] = 1;
                $paste['fork_count'] = 0;
                $paste['comment_count'] = 0;
                $comments = [];
                $paste['line_count'] = substr_count($paste['content'], "\n") + 1;
                $paste['character_count'] = strlen($paste['content']);
                $paste['file_size'] = formatBytes(strlen($paste['content']));
            }
        }
    }
}

// Helper function for file size formatting
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

$pageTitle = $paste ? ($paste['title'] ?: 'Untitled Paste') : 'Paste Not Found';
include '../includes/header.php';
?>

<main class="container py-5">
    <?php if ($error): ?>
    <!-- Error State -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                    </div>
                    <h3 class="mb-3">Paste Not Found</h3>
                    <p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="../index.php" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i>Go Home
                        </a>
                        <a href="create.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Create New Paste
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- First View Notice for Burn After Read -->
    <?php if ($paste['burn_after_read'] == 1 && isset($isCreatorView) && $isCreatorView): ?>
    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="alert alert-info border-0 shadow-sm burn-notice" role="alert" id="burnNotice">
                <!-- Close Button -->
                <button type="button" class="burn-close-btn" onclick="document.getElementById('burnNotice').style.display='none';" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                
                <!-- Main Notice -->
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center mb-3">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="me-3">
                            <i class="fas fa-info-circle fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">🔒 Burn After Read Enabled</h5>
                            <p class="mb-0 small">
                                <strong>This is your secure preview view.</strong> The next time anyone views this paste, it will be automatically burned and permanently deleted.
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0 me-5">
                        <span class="badge bg-warning text-dark px-3 py-2">
                            <i class="fas fa-fire me-1"></i>Next View Burns
                        </span>
                    </div>
                </div>
                
                <!-- Shareable Link Section -->
                <div class="burn-link-section">
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label fw-bold mb-2 d-block">
                                <i class="fas fa-share-alt me-1"></i>Shareable Link (Burns After First View)
                            </label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control burn-link-input" id="shareableBurnLink" 
                                       value="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/pages/view.php?id=' . htmlspecialchars($paste['id']); ?>" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('shareableBurnLink', this)">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Warning: Anyone with this link can burn the paste. Share it only with the intended recipient.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Burn After Read Notification -->
    <?php if (isset($burnNotification) && $burnNotification): ?>
    <div class="row justify-content-center mb-4">
        <div class="col-lg-10">
            <div class="burn-notification-container">
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center burning-effect" role="alert">
                    <div class="me-3 fire-icon-container">
                        <i class="fas fa-fire fa-2x text-danger flickering-fire"></i>
                        <div class="smoke-effect"></div>
                        <div class="smoke-effect smoke-2"></div>
                        <div class="smoke-effect smoke-3"></div>
                    </div>
                    <div class="flex-grow-1 burn-text">
                        <h5 class="alert-heading mb-1">🔥 Paste Burned</h5>
                        <p class="mb-0">This paste has been automatically deleted after being read. This was the final view - the content is no longer accessible.</p>
                    </div>
                    <div class="fire-particles">
                        <div class="particle"></div>
                        <div class="particle"></div>
                        <div class="particle"></div>
                        <div class="particle"></div>
                        <div class="particle"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .burn-notification-container {
        position: relative;
        overflow: hidden;
    }
    
    .burning-effect {
        background: linear-gradient(45deg, #ff6b35, #ff8c42, #ffd662, #ff6b35);
        background-size: 400% 400%;
        animation: burning-gradient 2s ease-in-out infinite;
        border: 2px solid #ff4500;
        position: relative;
        overflow: hidden;
    }
    
    @keyframes burning-gradient {
        0%, 100% { background-position: 0% 50%; }
        25% { background-position: 100% 50%; }
        50% { background-position: 50% 100%; }
        75% { background-position: 50% 0%; }
    }
    
    .flickering-fire {
        animation: fire-flicker 0.5s ease-in-out infinite alternate;
        filter: drop-shadow(0 0 10px #ff4500);
    }
    
    @keyframes fire-flicker {
        0% { 
            transform: scale(1) rotate(-2deg);
            color: #ff4500;
            text-shadow: 0 0 20px #ff6b35;
        }
        25% { 
            transform: scale(1.1) rotate(1deg);
            color: #ff6b35;
            text-shadow: 0 0 25px #ff8c42;
        }
        50% { 
            transform: scale(0.95) rotate(-1deg);
            color: #ff8c42;
            text-shadow: 0 0 15px #ffd662;
        }
        75% { 
            transform: scale(1.05) rotate(2deg);
            color: #ff4500;
            text-shadow: 0 0 30px #ff6b35;
        }
        100% { 
            transform: scale(1) rotate(0deg);
            color: #dc3545;
            text-shadow: 0 0 20px #ff4500;
        }
    }
    
    .smoke-effect {
        position: absolute;
        top: -10px;
        left: 50%;
        width: 20px;
        height: 20px;
        background: rgba(128, 128, 128, 0.6);
        border-radius: 50%;
        animation: smoke-rise 3s ease-out infinite;
        transform: translateX(-50%);
    }
    
    .smoke-2 {
        left: 40%;
        animation-delay: 0.5s;
        width: 15px;
        height: 15px;
    }
    
    .smoke-3 {
        left: 60%;
        animation-delay: 1s;
        width: 12px;
        height: 12px;
    }
    
    @keyframes smoke-rise {
        0% {
            transform: translateX(-50%) translateY(0) scale(1);
            opacity: 0.8;
            background: rgba(128, 128, 128, 0.6);
        }
        50% {
            transform: translateX(-50%) translateY(-30px) scale(1.5);
            opacity: 0.4;
            background: rgba(128, 128, 128, 0.3);
        }
        100% {
            transform: translateX(-50%) translateY(-60px) scale(2);
            opacity: 0;
            background: rgba(128, 128, 128, 0.1);
        }
    }
    
    .fire-particles {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        overflow: hidden;
    }
    
    .particle {
        position: absolute;
        width: 3px;
        height: 3px;
        background: #ff6b35;
        border-radius: 50%;
        animation: particle-float 2s ease-out infinite;
    }
    
    .particle:nth-child(1) {
        left: 20%;
        animation-delay: 0s;
        background: #ff4500;
    }
    
    .particle:nth-child(2) {
        left: 40%;
        animation-delay: 0.4s;
        background: #ff6b35;
    }
    
    .particle:nth-child(3) {
        left: 60%;
        animation-delay: 0.8s;
        background: #ff8c42;
    }
    
    .particle:nth-child(4) {
        left: 80%;
        animation-delay: 1.2s;
        background: #ffd662;
    }
    
    .particle:nth-child(5) {
        left: 90%;
        animation-delay: 1.6s;
        background: #ff4500;
    }
    
    @keyframes particle-float {
        0% {
            transform: translateY(60px) scale(1);
            opacity: 1;
        }
        25% {
            transform: translateY(40px) scale(1.2);
            opacity: 0.8;
        }
        50% {
            transform: translateY(20px) scale(0.8);
            opacity: 0.6;
        }
        75% {
            transform: translateY(5px) scale(1.1);
            opacity: 0.3;
        }
        100% {
            transform: translateY(-20px) scale(0.5);
            opacity: 0;
        }
    }
    
    .burn-text {
        animation: text-glow 2s ease-in-out infinite;
    }
    
    @keyframes text-glow {
        0%, 100% {
            text-shadow: 0 0 5px rgba(255, 107, 53, 0.5);
        }
        50% {
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.8), 0 0 30px rgba(255, 140, 66, 0.6);
        }
    }
    
    .fire-icon-container {
        position: relative;
    }
    
    /* Make the whole notification pulse with heat */
    .burning-effect {
        animation: burning-gradient 2s ease-in-out infinite, heat-pulse 3s ease-in-out infinite;
    }
    
    @keyframes heat-pulse {
        0%, 100% {
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.5);
            transform: scale(1);
        }
        50% {
            box-shadow: 0 0 40px rgba(255, 107, 53, 0.8), 0 0 60px rgba(255, 140, 66, 0.6);
            transform: scale(1.02);
        }
    }
    </style>
    <?php endif; ?>
    
    <!-- Paste Content -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Paste Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <!-- Title Row -->
                    <div class="d-flex justify-content-between align-items-start mb-3 paste-header">
                        <div class="flex-grow-1">
                            <h2 class="mb-0 fw-bold">
                                <?php echo htmlspecialchars($paste['title'] ?: 'Untitled Paste'); ?>
                            </h2>
                        </div>
                        <!-- Desktop Action Buttons -->
                        <div class="d-none d-md-flex gap-1 ms-3">
                            <button class="btn btn-outline-success btn-sm" onclick="viewRaw()" title="View Raw">
                                <i class="fas fa-file-alt me-1"></i>Raw
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="downloadPaste()" title="Download">
                                <i class="fas fa-download me-1"></i>Download
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="reportPaste()" title="Report">
                                <i class="fas fa-flag me-1"></i>Report
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" title="More Options">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="sharePaste()">
                                        <i class="fas fa-share me-2"></i>Share Paste
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="create.php?clone=<?php echo $pasteId; ?>">
                                        <i class="fas fa-clone me-2"></i>Clone Paste
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="forkPaste()">
                                        <i class="fas fa-code-branch me-2"></i>Fork Paste
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="addToFavorites()">
                                        <i class="far fa-heart me-2"></i>Add to Favorites
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                        <!-- Mobile Action Button -->
                        <div class="d-md-none ms-3">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Actions">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="viewRaw()">
                                        <i class="fas fa-file-alt me-2"></i>View Raw
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="downloadPaste()">
                                        <i class="fas fa-download me-2"></i>Download
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="sharePaste()">
                                        <i class="fas fa-share me-2"></i>Share Paste
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="reportPaste()">
                                        <i class="fas fa-flag me-2"></i>Report Paste
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="create.php?clone=<?php echo $pasteId; ?>">
                                        <i class="fas fa-clone me-2"></i>Clone Paste
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="forkPaste()">
                                        <i class="fas fa-code-branch me-2"></i>Fork Paste
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="addToFavorites()">
                                        <i class="far fa-heart me-2"></i>Add to Favorites
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Metadata Row -->
                    <div class="d-flex flex-wrap gap-3 text-muted small">
                        <span class="badge bg-primary-subtle text-primary px-3 py-2">
                            <i class="fas fa-code me-1"></i>
                            <?php echo htmlspecialchars($paste['language']); ?>
                        </span>
                        <span>
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('M j, Y \a\t g:i A', $paste['created_at']); ?>
                        </span>
                        <span>
                            <i class="fas fa-eye me-1"></i>
                            <?php echo number_format($paste['views']); ?> views
                        </span>
                        <?php if ($paste['expire_time']): ?>
                        <span class="text-warning" id="expiration-timer" data-expire-time="<?php echo $paste['expire_time']; ?>">
                            <i class="fas fa-clock me-1"></i>
                            <span id="countdown-text">Expires: <?php echo date('M j, Y', $paste['expire_time']); ?></span>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tags Row -->
                    <?php if (!empty($paste['tags'])): ?>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="text-muted small me-2">
                            <i class="fas fa-tags me-1"></i>Tags:
                        </span>
                        <?php 
                        $tags = explode(',', $paste['tags']);
                        foreach ($tags as $tag): 
                            $tag = trim($tag);
                            if (!empty($tag)):
                        ?>
                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1 small">
                            #<?php echo htmlspecialchars($tag); ?>
                        </span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

