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
