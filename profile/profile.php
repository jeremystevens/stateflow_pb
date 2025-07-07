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
    $stmt = $pdo->prepare("SELECT id, username, profile_image, tagline, website, created_at FROM users WHERE username = ?");
    $stmt->execute([$identifier]);
} else {
    $stmt = $pdo->prepare("SELECT id, username, profile_image, tagline, website, created_at FROM users WHERE id = ?");
    $stmt->execute([$identifier]);
}
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo 'User not found';
    exit();
}

$avatar = $user['profile_image'] ? '/uploads/avatars/' . $user['profile_image'] : '/img/default-avatar.svg';

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

// Fetch user stats
$statsStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total_pastes, \n" .
    "COALESCE(SUM(views),0) AS total_views, \n" .
    "COALESCE(SUM(fork_count),0) AS total_forks \n" .
    "FROM pastes WHERE user_id = ?"
);
$statsStmt->execute([$user['id']]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
$totalPastes = $stats['total_pastes'] ?? 0;
$totalViews  = $stats['total_views']  ?? 0;
$totalForks  = $stats['total_forks']  ?? 0;
$followers   = $user['followers_count'] ?? 0;
$following   = $user['following_count'] ?? 0;

$pageTitle = htmlspecialchars($user['username']);
include __DIR__ . '/../includes/header.php';
?>
<main class="container py-5">
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($avatar); ?>" alt="Avatar" class="rounded-circle" width="120" height="120">
            <div class="flex-grow-1">
                <h2 class="fw-bold mb-1"><?= htmlspecialchars($user['username']); ?></h2>
                <?php if (!empty($user['tagline'])): ?>
                    <p class="text-muted mb-1"><?= htmlspecialchars($user['tagline']); ?></p>
                <?php endif; ?>
                <p class="text-secondary small mb-0">
                    Member since <?= timeAgo($user['created_at']); ?>
                    <?php if (!empty($user['website'])): ?>
                        <a href="<?= htmlspecialchars($user['website']); ?>" target="_blank" class="ms-2 text-decoration-none">
                            <i class="fas fa-globe"></i>
                        </a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <ul class="nav nav-tabs profile-tabs nav-fill" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="fas fa-chart-bar me-2"></i>Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="achievements-tab" data-bs-toggle="tab" data-bs-target="#achievements" type="button" role="tab">
                    <i class="fas fa-trophy me-2"></i>Achievements
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="collections-tab" data-bs-toggle="tab" data-bs-target="#collections" type="button" role="tab">
                    <i class="fas fa-folder-open me-2"></i>Collections
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="pastes-tab" data-bs-toggle="tab" data-bs-target="#pastes" type="button" role="tab">
                    <i class="fas fa-code me-2"></i>Recent Pastes
                </button>
            </li>
        </ul>
        <div class="tab-content profile-content" id="profileTabsContent">
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="profile-stats mb-5">
                    <div class="profile-stat">
                        <i class="fas fa-file-code mb-1"></i>
                        <div class="h5 mb-0"><?= $totalPastes ?></div>
                        <small class="text-muted">Pastes</small>
                    </div>
                    <div class="profile-stat">
                        <i class="fas fa-eye mb-1"></i>
                        <div class="h5 mb-0"><?= $totalViews ?></div>
                        <small class="text-muted">Views</small>
                    </div>
                    <div class="profile-stat">
                        <i class="fas fa-code-branch mb-1"></i>
                        <div class="h5 mb-0"><?= $totalForks ?></div>
                        <small class="text-muted">Forks</small>
                    </div>
                    <div class="profile-stat">
                        <i class="fas fa-users mb-1"></i>
                        <div class="h5 mb-0"><?= $followers ?></div>
                        <small class="text-muted">Followers</small>
                    </div>
                    <div class="profile-stat">
                        <i class="fas fa-user-friends mb-1"></i>
                        <div class="h5 mb-0"><?= $following ?></div>
                        <small class="text-muted">Following</small>
                    </div>
                </div>
                <h5 class="fw-bold mb-3">Profile Summary</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="profile-summary h-100">
                            <p class="mb-0">
                                <?= !empty($user['tagline']) ? htmlspecialchars($user['tagline']) : 'No tagline provided.' ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-summary h-100">
                            <p class="mb-0">
                                <?php if (!empty($user['website'])): ?>
                                    <a href="<?= htmlspecialchars($user['website']); ?>" target="_blank">
                                        <?= htmlspecialchars($user['website']); ?>
                                    </a>
                                <?php else: ?>
                                    No website provided.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="achievements" role="tabpanel">
                <p class="text-muted">Coming soon...</p>
            </div>
            <div class="tab-pane fade" id="collections" role="tabpanel">
                <p class="text-muted">Coming soon...</p>
            </div>
            <div class="tab-pane fade" id="pastes" role="tabpanel">
                <p class="text-muted">Coming soon...</p>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

