/**
 * PasteForge - JavaScript Application
 * Enhanced interactivity and React-like behavior
 */

// Theme Management
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'dark';
        this.init();
    }

    init() {
        this.applyTheme();
        this.bindEvents();
    }

    applyTheme() {
        document.documentElement.setAttribute('data-bs-theme', this.theme);
        this.updateThemeIcon();
    }

    updateThemeIcon() {
        const themeIcon = document.getElementById('themeIcon');
        if (themeIcon) {
            themeIcon.className = this.theme === 'dark' 
                ? 'fas fa-sun' 
                : 'fas fa-moon';
        }
    }

    toggle() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', this.theme);
        this.applyTheme();
        
        // Add smooth transition effect
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    bindEvents() {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggle());
        }
    }
}

// Form Validation Enhancement
class FormValidator {
    constructor() {
        this.init();
    }

    init() {
        // Bootstrap validation
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Real-time validation
        this.addRealTimeValidation();
    }

    addRealTimeValidation() {
        const inputs = document.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });

            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) {
                    this.validateField(input);
                }
            });
        });
    }

    validateField(field) {
        const isValid = field.checkValidity();
        field.classList.toggle('is-valid', isValid);
        field.classList.toggle('is-invalid', !isValid);
    }
}

// Enhanced UI Interactions
class UIEnhancer {
    constructor() {
        this.init();
    }

    init() {
        this.addLoadingStates();
        this.addTooltips();
        this.addScrollEffects();
        this.addCardHoverEffects();
        this.addCopyToClipboard();
    }

    addLoadingStates() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                // Skip loading state for ZKE forms
                if (form.getAttribute('data-zke-form') === 'true') {
                    return;
                }
                
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';
                    
                    // Re-enable if form validation fails
                    setTimeout(() => {
                        if (!form.checkValidity()) {
                            submitBtn.classList.remove('loading');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    }, 100);
                }
            });
        });
    }

    addTooltips() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    addScrollEffects() {
        // Navbar background on scroll
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.style.backgroundColor = 'rgba(99, 102, 241, 0.95)';
                } else {
                    navbar.style.backgroundColor = '';
                }
            });
        }

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe cards and other elements
        const animatedElements = document.querySelectorAll('.card, .alert');
        animatedElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    }

    addCardHoverEffects() {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    }

    addCopyToClipboard() {
        // Disabled: We already have copy functionality in the toolbar
        // No need for embedded copy buttons that create visual artifacts
        return;
    }
}

// Keyboard Shortcuts
class KeyboardShortcuts {
    constructor() {
        this.init();
    }

    init() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K for search (if implemented)
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                // Focus search if available
            }

            // Alt + N for new paste
            if (e.altKey && e.key === 'n') {
                e.preventDefault();
                window.location.href = '/pages/create.php';
            }

            // Alt + H for home
            if (e.altKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = '/index.php';
            }

            // Alt + R for recent pastes
            if (e.altKey && e.key === 'r') {
                e.preventDefault();
                window.location.href = '/pages/recent.php';
            }
        });
    }
}

// Performance Monitoring
class PerformanceMonitor {
    constructor() {
        this.init();
    }

    init() {
        // Monitor page load time
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            console.log(`Page loaded in ${Math.round(loadTime)}ms`);
        });

        // Monitor navigation
        if ('navigation' in performance) {
            const navigation = performance.getEntriesByType('navigation')[0];
            if (navigation) {
                console.log('Navigation timing:', {
                    domContentLoaded: Math.round(navigation.domContentLoadedEventEnd),
                    loadComplete: Math.round(navigation.loadEventEnd)
                });
            }
        }
    }
}

// Expiration Timer
class ExpirationTimer {
    constructor() {
        this.timer = null;
        this.element = null;
        this.textElement = null;
        this.init();
    }

    init() {
        this.element = document.getElementById('expiration-timer');
        this.textElement = document.getElementById('countdown-text');
        
        if (this.element && this.textElement) {
            const expireTime = parseInt(this.element.dataset.expireTime);
            if (expireTime) {
                this.startCountdown(expireTime);
            }
        }
    }

    startCountdown(expireTime) {
        const updateTimer = () => {
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expireTime - now;

            if (timeLeft <= 0) {
                this.textElement.textContent = 'Expired';
                this.element.classList.remove('text-warning');
                this.element.classList.add('text-danger');
                if (this.timer) {
                    clearInterval(this.timer);
                }
                return;
            }

            const timeString = this.formatTimeLeft(timeLeft);
            this.textElement.textContent = `Expires in ${timeString}`;
        };

        // Update immediately, then every second
        updateTimer();
        this.timer = setInterval(updateTimer, 1000);
    }

    formatTimeLeft(seconds) {
        const units = [
            { name: 'year', seconds: 31536000 },
            { name: 'month', seconds: 2592000 },
            { name: 'week', seconds: 604800 },
            { name: 'day', seconds: 86400 },
            { name: 'hour', seconds: 3600 },
            { name: 'minute', seconds: 60 },
            { name: 'second', seconds: 1 }
        ];

        for (const unit of units) {
            const value = Math.floor(seconds / unit.seconds);
            if (value > 0) {
                const unitName = value === 1 ? unit.name : unit.name + 's';
                return `${value} ${unitName}`;
            }
        }

        return '0 seconds';
    }

    destroy() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}

// AJAX Comment and Reply System
class CommentSystem {
    constructor() {
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        // Handle main comment form
        const commentForm = document.querySelector('#comment-form form');
        if (commentForm) {
            commentForm.addEventListener('submit', (e) => this.handleCommentSubmit(e));
        }
        
        // Handle reply forms
        document.addEventListener('submit', (e) => {
            if (e.target.closest('.reply-form form')) {
                this.handleReplySubmit(e);
            }
        });
    }
    
    async handleCommentSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        // Find the submit button and show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const origText = submitBtn.textContent;
        submitBtn.textContent = 'Processing...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            console.log('Comment response status:', response.status);
            if (response.ok) {
                // Instead of complex AJAX reload, just reload page but stay on comments tab
                console.log('Comment submitted successfully, redirecting...');
                // Set hash first, then reload
                window.location.hash = '#comments';
                setTimeout(() => {
                    window.location.reload();
                }, 100);
            } else {
                console.error('Comment submission failed:', response.status);
                // Restore button state on error
                submitBtn.textContent = origText;
                submitBtn.disabled = false;
            }
        } catch (error) {
            console.error('Error submitting comment:', error);
            // Restore button state on error
            submitBtn.textContent = origText;
            submitBtn.disabled = false;
        }
    }
    
    async handleReplySubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const commentId = formData.get('comment_id');
        
        // Find the submit button and show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const origText = submitBtn.textContent;
        submitBtn.textContent = 'Processing...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            console.log('Reply response status:', response.status);
            if (response.ok) {
                // Instead of complex AJAX reload, just reload page but stay on comments tab
                console.log('Reply submitted successfully, redirecting...');
                // Set hash first, then reload
                window.location.hash = '#comments';
                setTimeout(() => {
                    window.location.reload();
                }, 100);
            } else {
                console.error('Reply submission failed:', response.status);
                // Restore button state on error
                submitBtn.textContent = origText;
                submitBtn.disabled = false;
            }
        } catch (error) {
            console.error('Error submitting reply:', error);
            // Restore button state on error
            submitBtn.textContent = origText;
            submitBtn.disabled = false;
        }
    }
    
    async reloadComments() {
        try {
            const response = await fetch(window.location.href);
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Find the comments section more specifically
            const currentComments = document.querySelector('#comments .tab-pane.active');
            const newComments = doc.querySelector('#comments .tab-pane.active');
            
            if (currentComments && newComments) {
                currentComments.innerHTML = newComments.innerHTML;
                console.log('Comments section updated successfully');
            } else {
                // Fallback: try to find any comments tab-pane
                const currentAnyComments = document.querySelector('#comments .tab-pane');
                const newAnyComments = doc.querySelector('#comments .tab-pane');
                
                if (currentAnyComments && newAnyComments) {
                    currentAnyComments.innerHTML = newAnyComments.innerHTML;
                    console.log('Comments section updated (fallback)');
                } else {
                    console.log('Could not find comments section, reloading page');
                    // If we can't find the right elements, just reload the page
                    window.location.reload();
                }
            }
        } catch (error) {
            console.error('Error reloading comments:', error);
            // Fallback to page reload if AJAX fails
            window.location.reload();
        }
    }
}

// Burn After Read Visibility Handler
function handleBurnAfterReadToggle() {
    const burnCheckbox = document.getElementById('burnAfterRead');
    const visibilitySelect = document.getElementById('visibility');
    const visibilityGroup = document.querySelector('.visibility-group');
    
    if (burnCheckbox && visibilitySelect) {
        function updateVisibilityForBurn() {
            if (burnCheckbox.checked) {
                // Store original visibility value
                if (!burnCheckbox.dataset.originalVisibility) {
                    burnCheckbox.dataset.originalVisibility = visibilitySelect.value;
                }
                
                // Set to unlisted and show notification
                visibilitySelect.value = 'unlisted';
                visibilitySelect.disabled = true;
                
                // Add or update notification
                let notification = document.getElementById('burn-visibility-notice');
                if (!notification) {
                    notification = document.createElement('div');
                    notification.id = 'burn-visibility-notice';
                    notification.className = 'alert alert-info mt-2 py-2';
                    notification.innerHTML = `
                        <i class="fas fa-info-circle me-1"></i>
                        <small>Burn After Read pastes are automatically set to <strong>Unlisted</strong> for privacy protection.</small>
                    `;
                    visibilityGroup.appendChild(notification);
                }
            } else {
                // Restore original visibility
                visibilitySelect.disabled = false;
                if (burnCheckbox.dataset.originalVisibility) {
                    visibilitySelect.value = burnCheckbox.dataset.originalVisibility;
                    delete burnCheckbox.dataset.originalVisibility;
                }
                
                // Remove notification
                const notification = document.getElementById('burn-visibility-notice');
                if (notification) {
                    notification.remove();
                }
            }
        }
        
        // Initial check
        updateVisibilityForBurn();
        
        // Listen for changes
        burnCheckbox.addEventListener('change', updateVisibilityForBurn);
    }
}

// Password visibility toggle for login and register forms
function initPasswordToggles() {
    const toggleIcons = document.querySelectorAll('.toggle-password');

    toggleIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const input = this.previousElementSibling;
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }

            const inner = this.querySelector('i');
            if (inner) {
                inner.classList.toggle('fa-eye');
                inner.classList.toggle('fa-eye-slash');
            }
        });
    });
}

// Initialize Application
document.addEventListener('DOMContentLoaded', () => {
    // Initialize all components
    new ThemeManager();
    new FormValidator();
    new UIEnhancer();
    new KeyboardShortcuts();
    new PerformanceMonitor();
    new ExpirationTimer();
    // new CommentSystem(); // Disabled - using inline comment system instead

    // Initialize burn after read visibility handler
    if (document.getElementById('burnAfterRead')) {
        handleBurnAfterReadToggle();
    }

    // Initialize password visibility toggles
    initPasswordToggles();

    // Add smooth loading effect
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.3s ease';
    
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);

    // Initialize syntax highlighting if Prism is available
    if (typeof Prism !== 'undefined') {
        Prism.highlightAll();
    }

    console.log('PasteForge initialized successfully! 🚀');
});

// Copy to clipboard function for burn after read and other copy operations
function copyToClipboard(elementId, button) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const text = element.value || element.textContent;
    
    // Use modern clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopySuccess(button);
        }).catch(err => {
            console.error('Failed to copy: ', err);
            fallbackCopyTextToClipboard(text, button);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyTextToClipboard(text, button);
    }
}

function fallbackCopyTextToClipboard(text, button) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    
    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess(button);
        } else {
            console.error('Fallback: Unable to copy');
        }
    } catch (err) {
        console.error('Fallback: Unable to copy', err);
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess(button) {
    const originalIcon = button.innerHTML;
    const originalClass = button.className;
    
    // Change button to show success
    button.innerHTML = '<i class="fas fa-check"></i>';
    button.className = button.className.replace('btn-outline-primary', 'btn-success');
    
    // Reset after 2 seconds
    setTimeout(() => {
        button.innerHTML = originalIcon;
        button.className = originalClass;
    }, 2000);
}

// Export for potential use in other scripts
window.PasteForge = {
    ThemeManager,
    FormValidator,
    UIEnhancer,
    KeyboardShortcuts,
    PerformanceMonitor,
    ExpirationTimer,
    CommentSystem
};
