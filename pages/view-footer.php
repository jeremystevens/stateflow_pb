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
            const newHTML = `<pre><code class="language-${language}">${escapedContent}</code></pre>`;
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
