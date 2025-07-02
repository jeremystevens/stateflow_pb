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

$enableCommandLine = ($paste && ($paste['language'] === 'shell' || $paste['language'] === 'powershell'));
$pageTitle = $paste ? ($paste['title'] ?: 'Untitled Paste') : 'Paste Not Found';
include '../includes/header.php';
?>

<?php if ($enableCommandLine): ?>
<!-- Prism.js Command-Line plugin -->
<link href="https://cdn.jsdelivr.net/npm/prismjs@1.30.0/themes/prism.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/prismjs@1.30.0/plugins/command-line/prism-command-line.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/prismjs@1.30.0/prism.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.30.0/components/prism-bash.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.30.0/components/prism-powershell.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.30.0/plugins/command-line/prism-command-line.min.js"></script>
<?php endif; ?>

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

            <!-- Tab Navigation -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 p-0">
                    <ul class="nav nav-tabs border-0" id="pasteViewTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active border-0 fw-semibold" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                                <i class="fas fa-code me-2"></i>Overview
                            </button>
                        </li>
                        <?php if ($paste['version_count'] > 1): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 fw-semibold" id="versions-tab" data-bs-toggle="tab" data-bs-target="#versions" type="button" role="tab">
                                <i class="fas fa-history me-2"></i>Versions <span class="badge bg-secondary ms-1"><?php echo $paste['version_count']; ?></span>
                            </button>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 fw-semibold" id="related-tab" data-bs-toggle="tab" data-bs-target="#related" type="button" role="tab">
                                <i class="fas fa-link me-2"></i>Related
                            </button>
                        </li>
                        <?php if ($paste['fork_count'] > 0): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 fw-semibold" id="forks-tab" data-bs-toggle="tab" data-bs-target="#forks" type="button" role="tab">
                                <i class="fas fa-code-branch me-2"></i>Forks <span class="badge bg-secondary ms-1"><?php echo $paste['fork_count']; ?></span>
                            </button>
                        </li>
                        <?php endif; ?>
                        <!-- Comments and Discussions tabs - hidden for ZKE pastes without decryption key -->
                        <li class="nav-item zke-protected-tab" role="presentation" style="<?php echo $paste['zero_knowledge'] ? 'display: none;' : ''; ?>">
                            <button class="nav-link border-0 fw-semibold" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">
                                <i class="fas fa-comments me-2"></i>Comments 
                                <?php if ($paste['comment_count'] > 0): ?>
                                <span class="badge bg-secondary ms-1"><?php echo $paste['comment_count']; ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <li class="nav-item zke-protected-tab" role="presentation" style="<?php echo $paste['zero_knowledge'] ? 'display: none;' : ''; ?>">
                            <button class="nav-link border-0 fw-semibold" id="discussions-tab" data-bs-toggle="tab" data-bs-target="#discussions" type="button" role="tab">
                                <i class="fas fa-users me-2"></i>Discussions
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-0">
                    <div class="tab-content" id="pasteViewTabContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="p-4">
                                <!-- AI Summary Section - Hidden until real AI analysis is available -->
                                <?php if (isset($paste['ai_analysis']) && !empty($paste['ai_analysis'])): ?>
                                <div class="alert alert-info border-0 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-robot fa-lg me-2"></i>
                                        <h6 class="mb-0 fw-bold">AI Code Analysis</h6>
                                    </div>
                                    <p class="mb-0 small">
                                        <?php echo htmlspecialchars($paste['ai_analysis']); ?>
                                    </p>
                                </div>
                                <?php endif; ?>

                                <!-- Statistics Dashboard -->
                                <div class="row g-3 mb-4">
                                    <div class="col-6 col-md-2">
                                        <div class="text-center p-3 bg-primary-subtle rounded">
                                            <div class="fw-bold text-primary fs-4"><?php echo number_format($paste['views']); ?></div>
                                            <small class="text-muted">Views</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <div class="text-center p-3 bg-success-subtle rounded">
                                            <div class="fw-bold text-success fs-4">0</div>
                                            <small class="text-muted">Likes</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <div class="text-center p-3 bg-info-subtle rounded">
                                            <div class="fw-bold text-info fs-4"><?php echo number_format($paste['character_count']); ?></div>
                                            <small class="text-muted">Characters</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <div class="text-center p-3 bg-warning-subtle rounded">
                                            <div class="fw-bold text-warning fs-4"><?php echo number_format($paste['line_count']); ?></div>
                                            <small class="text-muted">Lines</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <div class="text-center p-3 bg-secondary-subtle rounded">
                                            <div class="fw-bold text-secondary fs-4"><?php echo $paste['file_size']; ?></div>
                                            <small class="text-muted">Size</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <div class="text-center p-3 bg-purple-subtle rounded">
                                            <div class="fw-bold text-purple fs-4"><?php echo number_format($paste['comment_count']); ?></div>
                                            <small class="text-muted">Comments</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Code Content -->
                                <div class="position-relative">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-semibold mb-0">Code Content</h6>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="copyToClipboard()" title="Copy Code">
                                                <i class="fas fa-copy me-1"></i>Copy
                                            </button>
                                            <button class="btn btn-outline-secondary" onclick="printContent()" title="Print Code">
                                                <i class="fas fa-print me-1"></i>Print
                                            </button>
                                        </div>
                                    </div>
                                    <div class="code-container border rounded">
                                        <div class="code-with-lines">
                                            <div class="line-numbers-column">
                                                <?php 
                                                // Generate line numbers server-side
                                                for ($i = 1; $i <= $paste['line_count']; $i++) {
                                                    echo "<div class=\"line-number\">$i</div>";
                                                }
                                                ?>
                                            </div>
                                            <div class="code-column">
                                                <?php if (!empty($paste['zero_knowledge'])): ?>
                                                    <!-- Zero Knowledge Encrypted Content -->
                                                    <div id="pasteContent" data-encrypted="<?php echo htmlspecialchars($paste['content']); ?>" data-language="<?php echo htmlspecialchars($paste['language']); ?>">
                                                        <div class="alert alert-warning mb-3">
                                                            <i class="fas fa-lock me-2"></i>
                                                            This is an encrypted paste. Decryption key required.
                                                        </div>
                                                        <div class="bg-dark text-light p-3 rounded">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-lock text-warning me-3"></i>
                                                                <span class="text-muted">Encrypted content - add #zk=YOUR_KEY to URL to decrypt</span>
                                                            </div>
                                                        </div>
                                                        <div id="decryption-error" class="alert alert-danger d-none mt-3">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            Decryption failed. Please verify you have the complete URL with the decryption key.
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Regular Content -->
                                                    <pre class="<?php echo ($enableCommandLine ? 'command-line ' : ''); ?>language-<?php echo htmlspecialchars($paste['language']); ?>"><code class="language-<?php echo htmlspecialchars($paste['language']); ?>" id="pasteContent"><?php echo htmlspecialchars($paste['content']); ?></code></pre>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Versions Tab -->
                        <?php if ($paste['version_count'] > 1): ?>
                        <div class="tab-pane fade" id="versions" role="tabpanel">
                            <div class="p-4">
                                <h6 class="fw-semibold mb-3">Version History</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Version history feature coming soon! This will show all changes made to this paste over time.
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Related Tab -->
                        <div class="tab-pane fade" id="related" role="tabpanel">
                            <div class="p-4">
                                <h6 class="fw-semibold mb-3">Related Pastes</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-search me-2"></i>
                                    Smart content discovery based on language, tags, and similarity analysis coming soon!
                                </div>
                            </div>
                        </div>

                        <!-- Forks Tab -->
                        <?php if ($paste['fork_count'] > 0): ?>
                        <div class="tab-pane fade" id="forks" role="tabpanel">
                            <div class="p-4">
                                <h6 class="fw-semibold mb-3">Forks & Derivatives</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-code-branch me-2"></i>
                                    Fork management and derivative tracking coming soon!
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Comments Tab -->
                        <div class="tab-pane fade" id="comments" role="tabpanel">
                            <div class="p-4">
                                <?php if (!empty($success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex align-items-center mb-4">
                                    <div class="me-3">
                                        <span class="badge bg-primary px-3 py-2">
                                            <i class="fas fa-comments me-1"></i>
                                            Comments (<?php echo count($comments ?? []); ?>)
                                        </span>
                                    </div>
                                </div>

                                <!-- Comments List - Loaded Dynamically -->
                                <div id="comments-container" class="comments-container mb-4">
                                    <!-- Comments loaded via AJAX -->
                                </div>

                                <!-- Add Comment Form -->
                                <div class="add-comment-section">
                                    <h6 class="fw-semibold mb-3">Add a Comment</h6>
                                    <form id="comment-form" onsubmit="return submitComment(event)">
                                        <div class="mb-3">
                                            <textarea id="comment-content" class="form-control" name="content" rows="4" placeholder="Write your comment..." required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary" id="comment-submit-btn">
                                            <i class="fas fa-comment me-1"></i>Post Comment as Anonymous
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Discussions Tab -->
                        <div class="tab-pane fade" id="discussions" role="tabpanel">
                            <div class="p-4" id="discussions-container">
                                <?php if (!empty($viewThread) && $thread): ?>
                                <!-- Thread Detail View -->
                                <div id="thread-view" class="thread-detail-view">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <button class="btn btn-outline-secondary btn-sm" onclick="backToDiscussions()">
                                            <i class="fas fa-arrow-left me-1"></i>Back to Discussions
                                        </button>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <?php
                                            $categoryInfo = [
                                                'Q&A' => ['icon' => 'fas fa-question-circle', 'class' => 'bg-primary'],
                                                'Tip' => ['icon' => 'fas fa-lightbulb', 'class' => 'bg-warning text-dark'],
                                                'Idea' => ['icon' => 'fas fa-rocket', 'class' => 'bg-success'],
                                                'Bug' => ['icon' => 'fas fa-bug', 'class' => 'bg-danger'],
                                                'General' => ['icon' => 'fas fa-comments', 'class' => 'bg-info text-dark']
                                            ];
                                            $catInfo = $categoryInfo[$thread['category']] ?? ['icon' => 'fas fa-comment', 'class' => 'bg-secondary'];
                                            ?>
                                            <span class="badge <?= $catInfo['class'] ?>">
                                                <i class="<?= $catInfo['icon'] ?> me-1"></i><?= htmlspecialchars($thread['category']) ?>
                                            </span>
                                            <h5 class="mb-0"><?= htmlspecialchars($thread['title']) ?></h5>
                                        </div>
                                        <small class="text-muted">
                                            Started by <?= htmlspecialchars($thread['username']) ?> on 
                                            <?= date('M j, Y \a\t g:i A', $thread['created_at']) ?>
                                        </small>
                                    </div>
                                    
                                    <div class="thread-posts">
                                        <?php foreach ($threadPosts as $index => $post): ?>
                                        <div class="card mb-3 <?= $index === 0 ? 'border-primary' : '' ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($post['username']) ?>&background=6366f1&color=ffffff&size=32" 
                                                             alt="<?= htmlspecialchars($post['username']) ?>" class="rounded-circle" width="32" height="32">
                                                        <div>
                                                            <strong><?= htmlspecialchars($post['username']) ?></strong>
                                                            <?php if ($index === 0): ?>
                                                                <span class="badge bg-primary ms-1">OP</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted"><?= date('M j, Y \a\t g:i A', $post['created_at']) ?></small>
                                                </div>
                                                <div class="mt-2">
                                                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Add Reply</h6>
                                            </div>
                                            <div class="card-body">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="add_discussion_post">
                                                    <input type="hidden" name="thread_id" value="<?= $thread['id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="reply-username" class="form-label">Your Name</label>
                                                        <input type="text" class="form-control" name="username" id="reply-username" 
                                                               value="Anonymous" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="reply-content" class="form-label">Your Reply</label>
                                                        <textarea class="form-control" name="content" id="reply-content" 
                                                                 rows="4" placeholder="Share your thoughts..." required></textarea>
                                                    </div>
                                                    
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-reply me-1"></i>Post Reply
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <!-- Thread List View -->
                                <div id="thread-list" class="thread-list-view">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h6 class="fw-semibold mb-0">Discussions</h6>
                                        <button class="btn btn-primary btn-sm" onclick="showCreateThreadForm()">
                                            <i class="fas fa-plus me-1"></i>Start Discussion
                                        </button>
                                    </div>
                                    
                                    <!-- Category Overview -->
                                    <div class="row g-3 mb-4">
                                        <?php
                                        $threads = getDiscussionThreads($paste['id']);
                                        $categories = ['Q&A' => 'question-circle', 'Tip' => 'lightbulb', 'Idea' => 'rocket', 'Bug' => 'bug', 'General' => 'comments'];
                                        $categoryColors = ['Q&A' => 'primary', 'Tip' => 'warning', 'Idea' => 'success', 'Bug' => 'danger', 'General' => 'info'];
                                        $categoryCounts = array_count_values(array_column($threads, 'category'));
                                        
                                        foreach ($categories as $category => $icon):
                                            $count = $categoryCounts[$category] ?? 0;
                                            $color = $categoryColors[$category];
                                        ?>
                                        <div class="col-md-2">
                                            <div class="text-center p-3 border rounded discussion-category-card" data-category="<?php echo $category; ?>">
                                                <i class="fas fa-<?php echo $icon; ?> text-<?php echo $color; ?> fa-2x mb-2"></i>
                                                <div class="fw-bold"><?php echo $category; ?></div>
                                                <small class="text-muted"><?php echo $count; ?> topic<?php echo $count != 1 ? 's' : ''; ?></small>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Thread List -->
                                    <div class="discussion-threads">
                                        <?php if (empty($threads)): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-comments fa-3x mb-3 opacity-50"></i>
                                            <p class="mb-2">No discussions yet!</p>
                                            <p class="small">Start the conversation by creating the first discussion thread.</p>
                                        </div>
                                        <?php else: ?>
                                            <?php foreach ($threads as $thread): ?>
                                            <div class="discussion-thread-item mb-3 p-3 border rounded hover-shadow" onclick="viewThread(<?php echo $thread['id']; ?>)">
                                                <div class="d-flex align-items-start">
                                                    <div class="avatar-container me-3">
                                                        <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM5Y2E2ZjciLz4KPHN2ZyB4PSIxMCIgeT0iMTAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDIwIDIwIiBmaWxsPSIjZmZmIj4KPHBhdGggZD0iTTEwIDEwYy0xIDAtMS41LS41LTEuNS0xLjVzLjUtMS41IDEuNS0xLjUgMS41LjUgMS41IDEuNS0uNSAxLjUtMS41IDEuNXptNSAwYy40NSAwIDEuMi0uNSAxLjItMS41cy0uNS0xLjUtMS4yLTEuNS0xLjIuNS0xLjIgMS41LjUgMS41IDEuMiAxLjV6Ii8+Cjwvc3ZnPgo8L3N2Zz4K" 
                                                             alt="Avatar" class="rounded-circle" width="40" height="40">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <span class="badge bg-<?php echo $categoryColors[$thread['category']]; ?> me-2"><?php echo $thread['category']; ?></span>
                                                            <h6 class="mb-0 thread-title"><?php echo htmlspecialchars($thread['title']); ?></h6>
                                                        </div>
                                                        <div class="d-flex align-items-center text-muted small">
                                                            <span class="me-3">by <?php echo htmlspecialchars($thread['username']); ?></span>
                                                            <span class="me-3"><?php echo date('M j, Y \a\t g:i A', $thread['created_at']); ?></span>
                                                            <span><i class="fas fa-reply me-1"></i><?php echo $thread['reply_count']; ?> replies</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Create Thread Form -->
                                <div id="create-thread-form" class="create-thread-view" style="display: none;">
                                    <div class="d-flex align-items-center mb-4">
                                        <button class="btn btn-outline-secondary btn-sm me-3" onclick="hideCreateThreadForm()">
                                            <i class="fas fa-arrow-left me-1"></i>Back
                                        </button>
                                        <h6 class="fw-semibold mb-0">Start New Discussion</h6>
                                    </div>
                                    
                                    <form method="POST" id="create-thread-form-element">
                                        <input type="hidden" name="action" value="create_discussion_thread">
                                        <input type="hidden" name="paste_id" value="<?php echo $paste['id']; ?>">
                                        
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-8">
                                                <label for="thread-title" class="form-label">Discussion Title</label>
                                                <input type="text" class="form-control" id="thread-title" name="title" required 
                                                       placeholder="What would you like to discuss?">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="thread-category" class="form-label">Category</label>
                                                <select class="form-select" id="thread-category" name="category" required>
                                                    <option value="">Choose category...</option>
                                                    <option value="Q&A">Q&A - Questions & Answers</option>
                                                    <option value="Tip">Tip - Helpful Tips</option>
                                                    <option value="Idea">Idea - Improvements & Ideas</option>
                                                    <option value="Bug">Bug - Bug Reports</option>
                                                    <option value="General">General - General Discussion</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="thread-content" class="form-label">Initial Post</label>
                                            <textarea class="form-control" id="thread-content" name="content" rows="5" required 
                                                      placeholder="Start the discussion with your thoughts, questions, or ideas..."></textarea>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-1"></i>Create Discussion
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="hideCreateThreadForm()">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Paste View Functionality -->
    <script>
    // Copy to clipboard functionality
    function copyToClipboard() {
        const content = document.getElementById('pasteContent').textContent;
        navigator.clipboard.writeText(content).then(function() {
            showNotification('Code copied to clipboard!', 'success');
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
            showNotification('Failed to copy code', 'error');
        });
    }

    // Print functionality
    function printContent() {
        const content = document.getElementById('pasteContent').textContent;
        const title = '<?php echo htmlspecialchars($paste['title']); ?>';
        const language = '<?php echo htmlspecialchars($paste['language']); ?>';
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print: ${title}</title>
                    <style>
                        body { font-family: monospace; margin: 20px; }
                        h1 { font-size: 18px; margin-bottom: 10px; }
                        .meta { color: #666; margin-bottom: 20px; }
                        pre { white-space: pre-wrap; line-height: 1.4; }
                    </style>
                </head>
                <body>
                    <h1>${title}</h1>
                    <div class="meta">Language: ${language}</div>
                    <pre>${content}</pre>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    // View raw content
    function viewRaw() {
        const content = document.getElementById('pasteContent').textContent;
        const newWindow = window.open('', '_blank');
        newWindow.document.write('<pre style="font-family: monospace; white-space: pre-wrap;">' + 
                                content.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre>');
        newWindow.document.title = 'Raw Paste Content';
    }

    // Download paste as file
    function downloadPaste() {
        const content = document.getElementById('pasteContent').textContent;
        const filename = '<?php echo htmlspecialchars($paste['title'] ?: 'paste'); ?>.<?php echo htmlspecialchars($paste['language']); ?>';
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        showNotification('File downloaded successfully!', 'success');
    }

    // Print content area only
    function printContent() {
        const content = document.getElementById('pasteContent').textContent;
        const title = '<?php echo htmlspecialchars($paste['title'] ?: 'Untitled Paste'); ?>';
        const language = '<?php echo htmlspecialchars($paste['language']); ?>';
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>Print: ${title}</title>
                <style>
                    body {
                        font-family: 'Courier New', monospace;
                        margin: 20px;
                        line-height: 1.6;
                    }
                    .header {
                        border-bottom: 2px solid #333;
                        padding-bottom: 10px;
                        margin-bottom: 20px;
                    }
                    .title {
                        font-size: 18px;
                        font-weight: bold;
                        margin-bottom: 5px;
                    }
                    .meta {
                        color: #666;
                        font-size: 12px;
                    }
                    pre {
                        background: #f5f5f5;
                        padding: 15px;
                        border-radius: 5px;
                        white-space: pre-wrap;
                        word-wrap: break-word;
                        font-size: 12px;
                        line-height: 1.4;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="title">${title}</div>
                    <div class="meta">Language: ${language} | Generated from PasteForge</div>
                </div>
                <pre>${content.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        
        // Wait for content to load then print
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
        
        showNotification('Print dialog opened', 'info');
    }

    // Fork paste
    function forkPaste() {
        showNotification('Fork functionality coming soon!', 'info');
    }

    // Report paste
    function reportPaste() {
        if (confirm('Are you sure you want to report this paste for inappropriate content?')) {
            showNotification('Report submitted. Thank you for helping keep our community safe.', 'success');
        }
    }

    // Share paste
    function sharePaste() {
        const shareUrl = window.location.href;
        const title = '<?php echo htmlspecialchars($paste['title'] ?: 'Untitled Paste'); ?>';
        
        if (navigator.share) {
            // Use native sharing if available
            navigator.share({
                title: `PasteForge: ${title}`,
                text: `Check out this ${title} code snippet on PasteForge`,
                url: shareUrl
            }).then(() => {
                showNotification('Shared successfully!', 'success');
            }).catch(() => {
                // Fallback to clipboard
                copyUrlToClipboard(shareUrl);
            });
        } else {
            // Fallback to clipboard copy
            copyUrlToClipboard(shareUrl);
        }
    }

    // Copy URL to clipboard
    function copyUrlToClipboard(url) {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Paste URL copied to clipboard!', 'success');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('Paste URL copied to clipboard!', 'success');
        });
    }

    // Add to favorites
    function addToFavorites() {
        // Check if already favorited (in a real app, this would check user's favorites)
        const heartIcon = event.target.closest('a').querySelector('i');
        const isAlreadyFavorited = heartIcon.classList.contains('fas');
        
        if (isAlreadyFavorited) {
            // Remove from favorites
            heartIcon.classList.remove('fas');
            heartIcon.classList.add('far');
            event.target.closest('a').innerHTML = '<i class="far fa-heart me-2"></i>Add to Favorites';
            showNotification('Removed from favorites', 'info');
        } else {
            // Add to favorites
            heartIcon.classList.remove('far');
            heartIcon.classList.add('fas');
            event.target.closest('a').innerHTML = '<i class="fas fa-heart me-2"></i>Remove from Favorites';
            showNotification('Added to favorites!', 'success');
        }
    }

    // Toggle word wrap
    function toggleWordWrap() {
        const codeBlock = document.querySelector('#pasteContent');
        codeBlock.style.whiteSpace = codeBlock.style.whiteSpace === 'pre-wrap' ? 'pre' : 'pre-wrap';
        showNotification('Word wrap toggled', 'info');
    }



    // Show notification
    function showNotification(message, type = 'info') {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 'alert-info';
        
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 1055; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    // Toggle reply form visibility
    function toggleReplyForm(commentId) {
        const replyForm = document.getElementById('reply-form-' + commentId);
        if (replyForm.style.display === 'none' || replyForm.style.display === '') {
            replyForm.style.display = 'block';
            replyForm.querySelector('textarea').focus();
        } else {
            replyForm.style.display = 'none';
        }
    }

    // Hide reply form
    function hideReplyForm(commentId) {
        const replyForm = document.getElementById('reply-form-' + commentId);
        replyForm.style.display = 'none';
    }

    // Discussion Thread Functions
    function showCreateThreadForm() {
        document.getElementById('thread-list').style.display = 'none';
        document.getElementById('create-thread-form').style.display = 'block';
        document.getElementById('thread-view').style.display = 'none';
    }

    function hideCreateThreadForm() {
        document.getElementById('thread-list').style.display = 'block';
        document.getElementById('create-thread-form').style.display = 'none';
        document.getElementById('thread-view').style.display = 'none';
        // Clear form
        document.getElementById('create-thread-form-element').reset();
    }

    function viewThread(threadId) {
        window.location.href = `?id=<?= $pasteId ?>&view_thread=${threadId}`;
    }

    function backToDiscussions() {
        window.location.href = `?id=<?= $pasteId ?>#discussions`;
    }







    // Vanilla JavaScript comment system
    document.addEventListener('DOMContentLoaded', function() {
        let commentsLoaded = false;
        
        // Get the comments tab button
        const commentsTab = document.getElementById('comments-tab');
        const commentsContainer = document.getElementById('comments-container');
        
        if (commentsTab && commentsContainer) {
            // Load comments when Comments tab is clicked
            commentsTab.addEventListener('click', function() {
                console.log('Comments tab clicked, loading comments...');
                if (!commentsLoaded) {
                    setTimeout(function() {
                        loadComments();
                        commentsLoaded = true;
                    }, 100); // Small delay to ensure tab is active
                }
            });
            
            // Listen for Bootstrap tab shown event
            commentsTab.addEventListener('shown.bs.tab', function() {
                console.log('Comments tab shown event triggered');
                if (!commentsLoaded) {
                    loadComments();
                    commentsLoaded = true;
                }
            });
            
            // Check if we should load comments on page load (if coming from URL with #comments)
            setTimeout(function() {
                const commentsPane = document.getElementById('comments');
                if (window.location.hash === '#comments' || 
                    (commentsPane && (commentsPane.classList.contains('active') || commentsPane.classList.contains('show')))) {
                    console.log('Loading comments on page load...');
                    loadComments();
                    commentsLoaded = true;
                }
            }, 500); // Wait for page to fully load
        }
    });

    function loadComments() {
        console.log('Loading comments for paste ID: <?php echo $pasteId; ?>');
        
        const commentsContainer = document.getElementById('comments-container');
        if (!commentsContainer) {
            console.error('Comments container not found');
            return;
        }
        
        // Show loading indicator
        commentsContainer.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading comments...</div>';
        
        // Fetch comments via AJAX
        fetch('/api/getcomments.php?paste_id=<?php echo $pasteId; ?>')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                console.log('Comments loaded successfully');
                console.log('Response length:', html.length);
                commentsContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading comments:', error);
                commentsContainer.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load comments. Please refresh the page.</div>';
            });
    }

    function submitComment(event) {
        event.preventDefault();
        
        const content = $('#comment-content').val().trim();
        
        if (!content) {
            showNotification('Please enter a comment', 'error');
            return false;
        }
        
        // Show loading state
        const submitBtn = $('#comment-submit-btn');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Posting...').prop('disabled', true);
        
        // Submit via jQuery AJAX
        $.post('/api/helper.php', {
            action: 'add_comment',
            paste_id: '<?php echo $pasteId; ?>',
            content: content
        })
        .done(function(response) {
            if (response.success) {
                // Clear form
                $('#comment-content').val('');
                
                // Show success notification
                showNotification(response.message, 'success');
                
                // Reload comments immediately
                loadComments();
                
                // Switch to comments tab if not already there
                const commentsTab = $('[data-bs-target="#comments"]');
                if (commentsTab.length && !$('#comments').hasClass('active')) {
                    const tab = new bootstrap.Tab(commentsTab[0]);
                    tab.show();
                }
            } else {
                showNotification(response.error || 'Failed to post comment', 'error');
            }
        })
        .fail(function() {
            showNotification('Failed to post comment. Please try again.', 'error');
        })
        .always(function() {
            // Restore button state
            submitBtn.html(originalText).prop('disabled', false);
        });
        
        return false; // Prevent form submission
    }
    
    // Reply form toggle functions
    function toggleReplyForm(commentId) {
        const replyForm = document.getElementById('reply-form-' + commentId);
        if (replyForm) {
            replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
        }
    }

    function hideReplyForm(commentId) {
        const replyForm = document.getElementById('reply-form-' + commentId);
        if (replyForm) {
            replyForm.style.display = 'none';
        }
    }

    // Toggle replies visibility
    function toggleReplies(commentId) {
        const repliesContent = document.getElementById('replies-content-' + commentId);
        const repliesIcon = document.getElementById('replies-icon-' + commentId);
        
        if (repliesContent && repliesIcon) {
            if (repliesContent.style.display === 'none') {
                // Show replies
                repliesContent.style.display = 'block';
                repliesIcon.classList.remove('fa-chevron-right');
                repliesIcon.classList.add('fa-chevron-down');
            } else {
                // Hide replies
                repliesContent.style.display = 'none';
                repliesIcon.classList.remove('fa-chevron-down');
                repliesIcon.classList.add('fa-chevron-right');
            }
        }
    }

    function submitReplyDirect(commentId) {
        const content = $('#reply-content-' + commentId).val().trim();
        
        if (!content) {
            showNotification('Please enter a reply', 'error');
            return;
        }
        
        // Show loading state
        const submitBtn = $('#reply-submit-' + commentId);
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Posting...').prop('disabled', true);
        
        // Submit via jQuery AJAX
        $.post('/api/helper.php', {
            action: 'add_reply',
            paste_id: '<?php echo $pasteId; ?>',
            comment_id: commentId,
            content: content
        })
        .done(function(response) {
            if (response.success) {
                // Clear form and hide it
                $('#reply-content-' + commentId).val('');
                hideReplyForm(commentId);
                
                // Show success notification
                showNotification(response.message, 'success');
                
                // Reload comments immediately
                loadComments();
            } else {
                showNotification(response.error || 'Failed to post reply', 'error');
            }
        })
        .fail(function() {
            showNotification('Failed to post reply. Please try again.', 'error');
        })
        .always(function() {
            // Restore button state
            submitBtn.html(originalText).prop('disabled', false);
        });
    }

    // Show notification
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification-toast');
        existingNotifications.forEach(n => n.remove());
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `notification-toast alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Initialize syntax highlighting
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Prism !== 'undefined') {
            Prism.highlightAll();
        }
        
        // Auto-open Comments tab if hash is present
        if (window.location.hash === '#comments') {
            const commentsTab = document.querySelector('[data-bs-target="#comments"]');
            if (commentsTab) {
                const tab = new bootstrap.Tab(commentsTab);
                tab.show();
            }
        }
        
        // Auto-open Discussions tab if hash is present or viewing a thread
        <?php if (!empty($viewThread)): ?>
        const discussionsTab = document.querySelector('[data-bs-target="#discussions"]');
        if (discussionsTab) {
            const tab = new bootstrap.Tab(discussionsTab);
            tab.show();
        }
        <?php endif; ?>
        
        if (window.location.hash === '#discussions') {
            const discussionsTab = document.querySelector('[data-bs-target="#discussions"]');
            if (discussionsTab) {
                const tab = new bootstrap.Tab(discussionsTab);
                tab.show();
            }
        }
        
        // Zero-Knowledge Encryption decryption handler
        const hash = window.location.hash;
        if (hash.startsWith('#zk=')) {
            const keyMatch = hash.match(/#zk=([^&]+)/);
            const pasteContent = document.querySelector('#pasteContent');
            
            console.log('ZKE Detection Debug:');
            console.log('- Hash:', hash);
            console.log('- Key match:', keyMatch);
            console.log('- Paste content element:', pasteContent);
            console.log('- Has encrypted dataset:', pasteContent ? !!pasteContent.dataset.encrypted : 'NO ELEMENT');
            
            if (keyMatch && keyMatch[1] && pasteContent && pasteContent.dataset.encrypted) {
                const keyStr = decodeURIComponent(keyMatch[1]);
                const encryptedData = pasteContent.dataset.encrypted;
                const language = pasteContent.dataset.language || 'text';
                
                console.log('ZKE decryption starting with key length:', keyStr.length);
                console.log('Key (first 20 chars):', keyStr.substring(0, 20) + '...');
                console.log('Encrypted data length:', encryptedData.length);
                console.log('Encrypted data (first 50 chars):', encryptedData.substring(0, 50) + '...');
                console.log('Language:', language);
                
                // Show decryption in progress
                pasteContent.innerHTML = `
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                        </div>
                        <h5 class="text-muted mb-3">🔓 Decrypting Content...</h5>
                        <p class="text-muted">Processing zero-knowledge encrypted data...</p>
                    </div>
                `;
                
                // Decrypt the content
                decryptZKEContent(keyStr, encryptedData, language, pasteContent)
                    .then(() => {
                        console.log('ZKE decryption successful');
                        // Show success message briefly
                        showNotification('Content decrypted successfully!', 'success');
                        
                        // Show Comments and Discussions tabs since user has decryption key
                        const zkeProtectedTabs = document.querySelectorAll('.zke-protected-tab');
                        zkeProtectedTabs.forEach(tab => {
                            tab.style.display = '';
                        });
                        console.log('ZKE protected tabs (Comments/Discussions) are now visible');
                    })
                    .catch(error => {
                        console.error('ZKE decryption failed:', error);
                        const errorDiv = document.getElementById('decryption-error');
                        if (errorDiv) {
                            errorDiv.classList.remove('d-none');
                        }
                        // Show error state
                        pasteContent.innerHTML = `
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                                </div>
                                <h5 class="text-danger mb-3">🚫 Decryption Failed</h5>
                                <p class="text-muted">
                                    The decryption key is invalid or the data is corrupted.<br>
                                    Please verify you have the complete URL with the correct decryption key.
                                </p>
                            </div>
                        `;
                    });
            }
        }
    });
    
    // ZKE Decryption function using proper AES-GCM (matching create.php implementation)
    async function decryptZKEContent(keyStr, encryptedData, language, pasteContent) {
        try {
            console.log('Attempting AES-GCM decryption with key length:', keyStr.length);
            console.log('Encrypted data length:', encryptedData.length);
            
            // Convert base64 key to bytes - this should be exactly 32 bytes for AES-256
            const keyBytes = Uint8Array.from(atob(keyStr), c => c.charCodeAt(0));
            console.log('Key bytes length:', keyBytes.length);
            
            // Convert base64 encrypted data to bytes
            const encryptedBytes = Uint8Array.from(atob(encryptedData), c => c.charCodeAt(0));
            console.log('Encrypted bytes length:', encryptedBytes.length);
            
            // Extract IV (first 12 bytes) and ciphertext (rest)
            if (encryptedBytes.length < 12) {
                throw new Error('Encrypted data too short - missing IV');
            }
            
            const iv = encryptedBytes.slice(0, 12);
            const ciphertext = encryptedBytes.slice(12);
            
            console.log('IV length:', iv.length, 'Ciphertext length:', ciphertext.length);
            
            // Import the key for Web Crypto API
            const cryptoKey = await crypto.subtle.importKey(
                'raw',
                keyBytes,
                { name: 'AES-GCM' },
                false,
                ['decrypt']
            );
            
            console.log('Crypto key imported successfully');
            
            // Perform AES-GCM decryption
            const decryptedBuffer = await crypto.subtle.decrypt(
                {
                    name: 'AES-GCM',
                    iv: iv
                },
                cryptoKey,
                ciphertext
            );
            
            // Convert decrypted bytes to string
            const decryptedContent = new TextDecoder().decode(decryptedBuffer);
            console.log('Content decrypted successfully, length:', decryptedContent.length);
            
            // Replace encrypted content with decrypted content - try direct innerHTML approach
            console.log('Replacing DOM content with direct innerHTML approach...');
            console.log('pasteContent element before:', pasteContent);
            console.log('pasteContent current innerHTML:', pasteContent.innerHTML.substring(0, 200));
            console.log('First 100 chars of decrypted content:', decryptedContent.substring(0, 100));
            
            // Escape HTML entities in the decrypted content
            const escapedContent = decryptedContent
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            
            // Directly set innerHTML with pre/code structure
            const commandLine = (language === 'shell' || language === 'powershell');
            const preClass = commandLine ? `class="command-line language-${language}"` : `class="language-${language}"`;
            const newHTML = `<pre ${preClass}><code class="language-${language}">${escapedContent}</code></pre>`;
            console.log('New HTML to inject:', newHTML.substring(0, 200));
            
            pasteContent.innerHTML = newHTML;
            console.log('Set new innerHTML directly');
            
            // Remove the data attributes since we no longer need them
            pasteContent.removeAttribute('data-encrypted');
            pasteContent.removeAttribute('data-language');
            
            // Add a visual indicator that decryption worked
            pasteContent.style.border = '2px solid green';
            setTimeout(() => {
                pasteContent.style.border = '';
            }, 2000);
            
            console.log('DOM content replaced successfully with direct innerHTML');
            console.log('Final pasteContent innerHTML:', pasteContent.innerHTML.substring(0, 200));
            console.log('pasteContent element after:', pasteContent);
            
            // Apply syntax highlighting
            if (window.Prism) {
                setTimeout(() => {
                    Prism.highlightElement(code);
                    console.log('Syntax highlighting applied');
                }, 100);
            }
            
            // Re-generate line numbers for decrypted content
            const lineNumbersColumn = document.querySelector('.line-numbers-column');
            if (lineNumbersColumn) {
                const lineCount = decryptedContent.split('\n').length;
                lineNumbersColumn.innerHTML = '';
                for (let i = 1; i <= lineCount; i++) {
                    const lineDiv = document.createElement('div');
                    lineDiv.className = 'line-number';
                    lineDiv.textContent = i;
                    lineNumbersColumn.appendChild(lineDiv);
                }
                console.log('Line numbers regenerated:', lineCount, 'lines');
            }
            
        } catch (error) {
            console.error('AES-GCM decryption error:', error);
            console.error('Error details:', error.message, error.stack);
            throw error;
        }
    }
    
    // Notification system for ZKE status messages
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 4 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 4000);
    }
    </script>

        </div>
    </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
