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
                                            <i class="fas fa-comment me-1"></i>Post Comment as <?php echo htmlspecialchars($userData['username'] ?? 'Anonymous'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Discussions Tab -->
