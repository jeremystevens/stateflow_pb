<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../database/init.php';

$identifier = $_GET['uid'] ?? ($_SESSION['user_id'] ?? null);
if (!$identifier) {
    header('Location: /login.php');
    exit();
}

// Determine search field
if (isset($_GET['uid'])) {
    $stmt = $pdo->prepare("SELECT id, username, profile_image, tagline, website, created_at, followers_count, following_count FROM users WHERE username = ?");
    $stmt->execute([$identifier]);
} else {
    $stmt = $pdo->prepare("SELECT id, username, profile_image, tagline, website, created_at, followers_count, following_count FROM users WHERE id = ?");
    $stmt->execute([$identifier]);
}
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo 'User not found';
    exit();
}

$avatar = $user['profile_image'] ? '/uploads/avatars/' . $user['profile_image'] : '/img/default-avatar.svg';

// Fetch user statistics
$statsStmt = $pdo->prepare("SELECT COUNT(*) AS total_pastes, COALESCE(SUM(views),0) AS total_views, COALESCE(SUM(fork_count),0) AS total_forks FROM pastes WHERE user_id = ?");
$statsStmt->execute([$user['id']]);
$stats = $statsStmt->fetch();
$totalPastes = (int)($stats['total_pastes'] ?? 0);
$totalViews = (int)($stats['total_views'] ?? 0);
$totalLikes = 0;
// Check for paste_likes table
$checkLikes = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='paste_likes'")->fetchColumn();
if ($checkLikes) {
    $likesStmt = $pdo->prepare("SELECT COUNT(*) FROM paste_likes pl JOIN pastes p ON pl.paste_id = p.id WHERE p.user_id = ?");
    $likesStmt->execute([$user['id']]);
    $totalLikes = (int)$likesStmt->fetchColumn();
}
$followers = (int)($user['followers_count'] ?? 0);
$following = (int)($user['following_count'] ?? 0);
$totalEngagement = $totalViews + $totalLikes;
$avgViews = $totalPastes > 0 ? round($totalViews / $totalPastes, 2) : 0;

function timeAgo($timestamp) {
    $timestamp = is_numeric($timestamp) ? (int)$timestamp : strtotime($timestamp);
    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $m = floor($diff / 60);
        return $m . ' minute' . ($m > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $h = floor($diff / 3600);
        return $h . ' hour' . ($h > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $d = floor($diff / 86400);
        return $d . ' day' . ($d > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $mo = floor($diff / 2592000);
        return $mo . ' month' . ($mo > 1 ? 's' : '') . ' ago';
    }
    $y = floor($diff / 31536000);
    return $y . ' year' . ($y > 1 ? 's' : '') . ' ago';
}

$pageTitle = htmlspecialchars($user['username']);
include __DIR__ . '/../includes/header.php';
?>
<main class="container-fluid px-4 py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg mb-4">
                <div class="card-body text-center p-4">
                    <img src="<?= htmlspecialchars($avatar); ?>" alt="Avatar" class="rounded-circle mb-3" width="120" height="120">
                    <h2 class="fw-bold mb-0 text-white"><?= htmlspecialchars($user['username']); ?></h2>
                    <?php if (!empty($user['tagline'])): ?>
                        <p class="text-muted mb-1 small"><?= htmlspecialchars($user['tagline']); ?></p>
                    <?php endif; ?>
                    <p class="text-secondary small mb-2">Member since <?= timeAgo($user['created_at']); ?></p>
                    <?php if (!empty($user['website'])): ?>
                        <a href="<?= htmlspecialchars($user['website']); ?>" target="_blank" class="text-decoration-none">
                            <i class="fas fa-globe fa-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 p-0">
                    <ul class="nav nav-tabs border-0" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active border-0 fw-semibold" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                                <i class="fas fa-chart-bar me-2"></i>Overview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 fw-semibold" id="achievements-tab" data-bs-toggle="tab" data-bs-target="#achievements" type="button" role="tab">
                                <i class="fas fa-trophy me-2"></i>Achievements
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 fw-semibold" id="collections-tab" data-bs-toggle="tab" data-bs-target="#collections" type="button" role="tab">
                                <i class="fas fa-folder-open me-2"></i>Collections
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 fw-semibold" id="pastes-tab" data-bs-toggle="tab" data-bs-target="#pastes" type="button" role="tab">
                                <i class="fas fa-code me-2"></i>Recent Pastes
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="row g-3 mb-4 text-center">
                                <div class="col-6 col-md-4">
                                    <div class="p-3 rounded text-white bg-primary h-100">
                                        <i class="fas fa-file-code fa-2x"></i>
                                        <div class="fw-bold mt-2"><?= $totalPastes ?></div>
                                        <small>Total Pastes</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="p-3 rounded text-white bg-success h-100">
                                        <i class="fas fa-eye fa-2x"></i>
                                        <div class="fw-bold mt-2"><?= $totalViews ?></div>
                                        <small>Total Views</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="p-3 rounded text-white bg-danger h-100">
                                        <i class="fas fa-heart fa-2x"></i>
                                        <div class="fw-bold mt-2"><?= $totalLikes ?></div>
                                        <small>Total Likes</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="p-3 rounded text-white bg-info h-100">
                                        <i class="fas fa-users fa-2x"></i>
                                        <div class="fw-bold mt-2"><?= $followers ?></div>
                                        <small>Followers</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="p-3 rounded text-white bg-warning h-100">
                                        <i class="fas fa-user-plus fa-2x"></i>
                                        <div class="fw-bold mt-2"><?= $following ?></div>
                                        <small>Following</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Profile Summary</h6>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Account Status</span>
                                        <span class="fw-semibold">Active Member</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Join Date</span>
                                        <span class="fw-semibold"><?= date('F j, Y', is_numeric($user['created_at']) ? (int)$user['created_at'] : strtotime($user['created_at'])) ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Activity</span>
                                        <span class="fw-semibold">Regular contributor</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Total Engagement</span>
                                        <span class="fw-semibold"><?= $totalEngagement ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Average Views</span>
                                        <span class="fw-semibold"><?= $avgViews ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Social Reach</span>
                                        <span class="fw-semibold"><?= $followers ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="achievements" role="tabpanel">...</div>
                        <div class="tab-pane fade" id="collections" role="tabpanel">...</div>
                        <div class="tab-pane fade" id="pastes" role="tabpanel">...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

