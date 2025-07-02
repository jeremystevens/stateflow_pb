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
                                                    <pre><code class="language-<?php echo htmlspecialchars($paste['language']); ?>" id="pasteContent"><?php echo htmlspecialchars($paste['content']); ?></code></pre>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

