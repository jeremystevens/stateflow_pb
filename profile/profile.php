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
    "COALESCE(SUM(fork_count),0) AS total_forks, \n" .
    "SUM(CASE WHEN parent_paste_id IS NOT NULL THEN 1 ELSE 0 END) AS total_chains \n" .
    "FROM pastes WHERE user_id = ?"
);
$statsStmt->execute([$user['id']]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
$totalPastes = $stats['total_pastes'] ?? 0;
$totalViews  = $stats['total_views']  ?? 0;
$totalForks  = $stats['total_forks']  ?? 0;
$totalChains = $stats['total_chains'] ?? 0;
$followers   = $user['followers_count'] ?? 0;
$following   = $user['following_count'] ?? 0;

// Data for "Pastes Created Per Day" chart (last 7 days)
$weekStmt = $pdo->prepare(
    "SELECT strftime('%Y-%m-%d', datetime(created_at, 'unixepoch')) AS day, " .
    "COUNT(*) AS count FROM pastes WHERE user_id = ? " .
    "AND created_at >= strftime('%s','now','-6 days') GROUP BY day ORDER BY day"
);
$weekStmt->execute([$user['id']]);
$weekData = $weekStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$chart1Labels = [];
$chart1Values = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart1Labels[] = date('D', strtotime($date));
    $chart1Values[] = isset($weekData[$date]) ? (int)$weekData[$date] : 0;
}

// Data for "Engagement Metrics" chart
$engageStmt = $pdo->prepare(
    "SELECT " .
    "COALESCE(SUM(views),0) AS total_views, " .
    "COALESCE(SUM(fork_count),0) AS total_forks, " .
    "SUM(CASE WHEN parent_paste_id IS NOT NULL THEN 1 ELSE 0 END) AS total_chains " .
    "FROM pastes WHERE user_id = ?"
);
$engageStmt->execute([$user['id']]);
$engage = $engageStmt->fetch(PDO::FETCH_ASSOC);
$commentStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM comments c JOIN pastes p ON c.paste_id = p.id WHERE p.user_id = ?"
);
$commentStmt->execute([$user['id']]);
$totalComments = (int)$commentStmt->fetchColumn();

$chart2Labels = ['Views', 'Likes', 'Forks', 'Chains', 'Comments'];
$chart2Values = [
    (int)($engage['total_views'] ?? 0),
    0, // Likes table not implemented
    (int)($engage['total_forks'] ?? 0),
    (int)($engage['total_chains'] ?? 0),
    $totalComments
];

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
                        <i class="fas fa-link mb-1"></i>
                        <div class="h5 mb-0"><?= $totalChains ?></div>
                        <small class="text-muted">Chains</small>
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
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="profile-chart">
                            <canvas id="chart1" style="height:300px"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-chart">
                            <canvas id="chart2" style="height:300px"></canvas>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const getTextColor = () => {
        const isDarkMode = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        return isDarkMode ? '#f0f0f0' : '#333';
    };
    let textColor = getTextColor();

    const ctx1 = document.getElementById('chart1').getContext('2d');
    const chart1 = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart1Labels) ?>,
            datasets: [{
                label: 'Pastes Created',
                data: <?= json_encode($chart1Values) ?>,
                backgroundColor: 'rgba(59,130,246,0.6)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: { display: true, text: 'Pastes Created Per Day', color: textColor },
                legend: { labels: { color: textColor } }
            },
            scales: {
                x: { ticks: { color: textColor } },
                y: { ticks: { color: textColor } }
            }
        }
    });

    const ctx2 = document.getElementById('chart2').getContext('2d');
    const chart2 = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart2Labels) ?>,
            datasets: [{
                label: 'Total',
                data: <?= json_encode($chart2Values) ?>,
                backgroundColor: 'rgba(34,197,94,0.6)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: { display: true, text: 'Engagement Metrics', color: textColor },
                legend: { labels: { color: textColor } }
            },
            scales: {
                x: { ticks: { color: textColor } },
                y: { ticks: { color: textColor } }
            }
        }
    });

    document.addEventListener('themeChanged', () => {
        textColor = getTextColor();
        [chart1, chart2].forEach(chart => {
            chart.options.plugins.title.color = textColor;
            chart.options.plugins.legend.labels.color = textColor;
            chart.options.scales.x.ticks.color = textColor;
            chart.options.scales.y.ticks.color = textColor;
            chart.update();
        });
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

