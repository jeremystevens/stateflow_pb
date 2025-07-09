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

                                <?php require 'view-paste-metadata.php'; ?>

                                <?php require 'view-code.php'; ?>
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

                        <?php require 'view-comments.php'; ?>

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
                                        <?php
                                        $canDeleteThread = !empty($userData['id']) && (
                                            (!empty($thread['user_id']) && $thread['user_id'] == $userData['id']) ||
                                            (empty($thread['user_id']) && $thread['username'] === ($userData['username'] ?? ''))
                                        );
                                        if ($canDeleteThread): ?>
                                        <button class="btn btn-link btn-sm text-danger" onclick="deleteDiscussionThread(<?= $thread['id'] ?>)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
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
                                        <div class="card mb-3 <?= $index === 0 ? 'border-primary' : '' ?>" id="post-<?= $post['id'] ?>">
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
                                                    <div class="d-flex align-items-center gap-2">
                                                        <small class="text-muted"><?= date('M j, Y \a\t g:i A', $post['created_at']) ?></small>
                                                        <?php
                                                        $canDeletePost = !empty($userData['id']) && (
                                                            (!empty($post['user_id']) && $post['user_id'] == $userData['id']) ||
                                                            (empty($post['user_id']) && $post['username'] === ($userData['username'] ?? ''))
                                                        );
                                                        if ($canDeletePost): ?>
                                                        <button class="btn btn-link btn-sm text-danger p-0" onclick="deleteDiscussionPost(<?= $post['id'] ?>)">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
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
                                                    
                                                    <?php if (!empty($userData['username'])): ?>
                                                        <input type="hidden" name="username" value="<?= htmlspecialchars($userData['username']) ?>">
                                                        <p class="mb-3">Replying as <?= htmlspecialchars($userData['username']) ?></p>
                                                    <?php else: ?>
                                                    <div class="mb-3">
                                                        <label for="reply-username" class="form-label">Your Name</label>
                                                        <input type="text" class="form-control" name="username" id="reply-username"
                                                               value="Anonymous" required>
                                                    </div>
                                                    <?php endif; ?>
                                                    
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
                                            <div class="discussion-thread-item mb-3 p-3 border rounded hover-shadow position-relative" id="thread-<?= $thread['id']; ?>" onclick="viewThread(<?php echo $thread['id']; ?>)">
                                                <div class="d-flex align-items-start w-100">
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
                                                    <?php
                                                    $canDeleteList = !empty($userData['id']) && (
                                                        (!empty($thread['user_id']) && $thread['user_id'] == $userData['id']) ||
                                                        (empty($thread['user_id']) && $thread['username'] === ($userData['username'] ?? ''))
                                                    );
                                                    if ($canDeleteList): ?>
                                                    <button class="btn btn-link btn-sm text-danger position-absolute top-0 end-0" onclick="deleteDiscussionThread(<?= $thread['id']; ?>); event.stopPropagation();">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                    <?php endif; ?>
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

