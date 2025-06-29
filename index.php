<?php
require_once 'includes/db.php';

// Get recent pastes for homepage
$recentPastes = getRecentPastes(5);

$pageTitle = "PasteForge";
include 'includes/header.php';
?>

<main class="container-fluid px-4 py-5">
    <!-- Hero Section -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-8 text-center">
            <div class="hero-card card border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="hero-icon mb-4">
                        <i class="fas fa-code fa-3x text-primary"></i>
                    </div>
                    <h1 class="display-4 fw-bold mb-3">PasteForge</h1>
                    <p class="lead mb-4 text-muted">
                        Share code snippets, text, and notes with a beautiful, modern interface. 
                        Fast, secure, and easy to use.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="pages/create.php" class="btn btn-primary btn-lg px-4 me-md-2">
                            <i class="fas fa-plus me-2"></i>Create Paste
                        </a>
                        <a href="pages/recent.php" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="fas fa-clock me-2"></i>Recent Pastes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Powerful Features Section -->
    <div class="text-center mb-5">
        <h2 class="fw-bold">Powerful Features</h2>
        <p class="text-muted">Everything you need to share and manage your code</p>
    </div>
    
    <div class="row g-4 mb-5">
        <!-- Smart Code Sharing -->
        <div class="col-md-4">
            <div class="feature-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-code fa-2x text-primary"></i>
                    </div>
                    <h5 class="card-title">Smart Code Sharing</h5>
                    <p class="card-text text-muted mb-3">
                        Share code snippets with syntax highlighting for 50+ programming languages. Public or private with optional password protection.
                    </p>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Syntax highlighting with Prism.js</li>
                        <li><i class="fas fa-check text-success me-2"></i>Link numbers and copy functionality</li>
                        <li><i class="fas fa-check text-success me-2"></i>Multiple file download options</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- AI Code Summaries -->
        <div class="col-md-4">
            <div class="feature-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-robot fa-2x text-purple"></i>
                    </div>
                    <h5 class="card-title">AI Code Summaries</h5>
                    <p class="card-text text-muted mb-3">
                        Get intelligent explanations and in-depth code with our AI-powered analysis system that understands context and functionality.
                    </p>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Automated code explanations</li>
                        <li><i class="fas fa-check text-success me-2"></i>Language-aware analysis</li>
                        <li><i class="fas fa-check text-success me-2"></i>Quality-assured summaries</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Smart Discovery -->
        <div class="col-md-4">
            <div class="feature-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-lightbulb fa-2x text-warning"></i>
                    </div>
                    <h5 class="card-title">Smart Discovery</h5>
                    <p class="card-text text-muted mb-3">
                        Discover related pastes and similar code through our intelligent recommendation engine based on language and content.
                    </p>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Related paste suggestions</li>
                        <li><i class="fas fa-check text-success me-2"></i>Cross-user discovery</li>
                        <li><i class="fas fa-check text-success me-2"></i>Tag-based grouping</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Organization Tools -->
        <div class="col-md-4">
            <div class="feature-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-folder-open fa-2x text-info"></i>
                    </div>
                    <h5 class="card-title">Organization Tools</h5>
                    <p class="card-text text-muted mb-3">
                        Organize your code with collections, projects, and tagging features. Keep everything structured and easy to find.
                    </p>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Custom collections</li>
                        <li><i class="fas fa-check text-success me-2"></i>Project management</li>
                        <li><i class="fas fa-check text-success me-2"></i>Advanced tagging</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Collaboration -->
        <div class="col-md-4">
            <div class="feature-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-users fa-2x text-success"></i>
                    </div>
                    <h5 class="card-title">Collaboration</h5>
                    <p class="card-text text-muted mb-3">
                        Connect with other developers through following, discussions, and collaborative projects. Build your coding community.
                    </p>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Follow system</li>
                        <li><i class="fas fa-check text-success me-2"></i>Paste discussions</li>
                        <li><i class="fas fa-check text-success me-2"></i>Comments & feedback</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Version Control -->
        <div class="col-md-4">
            <div class="feature-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-code-branch fa-2x text-danger"></i>
                    </div>
                    <h5 class="card-title">Version Control</h5>
                    <p class="card-text text-muted mb-3">
                        Track changes with versioning, create forks, and build paste collections. Never lose track of your code evolution.
                    </p>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Paste versioning</li>
                        <li><i class="fas fa-check text-success me-2"></i>Forking system</li>
                        <li><i class="fas fa-check text-success me-2"></i>Clean branching</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy & Security Section -->
    <div class="text-center mb-5">
        <h2 class="fw-bold">Privacy & Security</h2>
        <p class="text-muted">Your code stays private and secure with advanced protection features</p>
    </div>
    
    <div class="row g-4 mb-5">
        <!-- Zero Knowledge Encryption -->
        <div class="col-md-4">
            <div class="feature-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shield-alt fa-2x text-warning"></i>
                    </div>
                    <h5 class="card-title">Zero Knowledge Encryption</h5>
                    <p class="card-text text-muted mb-3">
                        Your content is encrypted in your browser before being sent to our servers. We never see your original text, ensuring maximum privacy.
                    </p>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Client-side encryption</li>
                        <li><i class="fas fa-check text-success me-2"></i>Server never sees original content</li>
                        <li><i class="fas fa-check text-success me-2"></i>Decryption key in URL fragment</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Burn After Read -->
        <div class="col-md-4">
            <div class="feature-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-fire fa-2x text-danger"></i>
                    </div>
                    <h5 class="card-title">Burn After Read</h5>
                    <p class="card-text text-muted mb-3">
                        Self-destructing pastes that are automatically deleted after the first view. Perfect for sharing sensitive information securely.
                    </p>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Auto-delete after first read</li>
                        <li><i class="fas fa-check text-success me-2"></i>No trace left behind</li>
                        <li><i class="fas fa-check text-success me-2"></i>Perfect for sensitive data</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Password Protection -->
        <div class="col-md-4">
            <div class="feature-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-lock fa-2x text-primary"></i>
                    </div>
                    <h5 class="card-title">Password Protected Pastes</h5>
                    <p class="card-text text-muted mb-3">
                        Add an extra layer of security with password protection. Only users with the correct password can view your content.
                    </p>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Custom password protection</li>
                        <li><i class="fas fa-check text-success me-2"></i>Secure hash storage</li>
                        <li><i class="fas fa-check text-success me-2"></i>Multiple security layers</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Pastes Section -->
    <?php if (!empty($recentPastes)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Recent Pastes</h4>
                        <a href="pages/recent.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-right me-1"></i>View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentPastes as $paste): ?>
                        <a href="pages/view.php?id=<?php echo htmlspecialchars($paste['id']); ?>" 
                           class="list-group-item list-group-item-action border-0 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($paste['title'] ?: 'Untitled'); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-code me-1"></i>
                                        <?php echo htmlspecialchars($paste['language']); ?>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($paste['created_at'])); ?>
                                </small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
