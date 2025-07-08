<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../database/init.php';
require_once __DIR__ . '/../includes/achievements.php';
loadAchievementsFromCSV(__DIR__ . '/../database/achievements.csv');

$usernameParam = filter_input(INPUT_GET, 'user', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (!$usernameParam) {
    http_response_code(404);
    echo 'User not specified';
    exit();
}

$stmt = $pdo->prepare(
    "SELECT id, username, profile_image, tagline, website, created_at
     FROM users WHERE username = ?"
);
$stmt->execute([$usernameParam]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo 'User not found';
    exit();
}

$avatar = $user['profile_image'] ? '/uploads/avatars/' . $user['profile_image'] : '/img/default-avatar.svg';

// Number of pastes to display per page
$perPage = 5;
// Determine the requested page number
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => ['default' => 1, 'min_range' => 1]
]);

$profile_user_id = $user['id'];
$profile_username = $user['username'];

// Count total pastes for pagination
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM pastes WHERE user_id = :uid"
);
$countStmt->execute([':uid' => $profile_user_id]);
$totalPasteCount = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalPasteCount / $perPage);
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// Fetch paginated pastes for the profile user
$userPastes = fetchUserPastes($pdo, $profile_user_id, $perPage, $offset);

/**
 * Build pagination URL preserving existing query parameters.
 */
function buildPageUrl($p) {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}

/**
 * Retrieve a list of pastes for a given user.
 */
function fetchUserPastes(PDO $pdo, $uid, $limit, $offset) {
    $stmt = $pdo->prepare(
        "SELECT id, title, language, created_at FROM pastes
         WHERE user_id = :uid
         ORDER BY created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


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

$achievementStats = getUserAchievementStats($user['id']);
$unlockedAchievements = getUnlockedAchievements($user['id']);
$inProgressAchievements = getInProgressAchievements($user['id']);

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
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="profile-stat">
                            <i class="fas fa-trophy mb-1"></i>
                            <div class="h5 mb-0"><?= $achievementStats['unlocked'] ?></div>
                            <small class="text-muted">Achievements Unlocked</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="profile-stat">
                            <i class="fas fa-percentage mb-1"></i>
                            <div class="h5 mb-0"><?= $achievementStats['completion'] ?>%</div>
                            <small class="text-muted">Completion Rate</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="profile-stat">
                            <i class="fas fa-star mb-1"></i>
                            <div class="h5 mb-0"><?= $achievementStats['points'] ?></div>
                            <small class="text-muted">Total Points</small>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Unlocked Achievements</h5>
                <div class="row g-3 mb-4">
                    <?php foreach ($unlockedAchievements as $a): ?>
                    <div class="col-md-4">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <i class="fas <?= htmlspecialchars($a['icon']) ?> fa-2x mb-2 text-warning"></i>
                                <h6 class="card-title mb-1"><?= htmlspecialchars($a['name']) ?></h6>
                                <p class="small text-muted mb-2"><?= htmlspecialchars($a['description']) ?></p>
                                <span class="badge bg-success">Unlocked <?= date('Y-m-d', $a['unlocked_at']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($unlockedAchievements)): ?>
                        <p class="text-muted">No achievements unlocked yet.</p>
                    <?php endif; ?>
                </div>

                <h5 class="mb-3">In Progress</h5>
                <?php foreach ($inProgressAchievements as $a): ?>
                <?php $percent = round(($a['current_progress'] / $a['target_progress']) * 100); ?>
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas <?= htmlspecialchars($a['icon']) ?> me-2 text-warning"></i>
                        <div class="flex-grow-1">
                            <strong><?= htmlspecialchars($a['name']) ?></strong>
                            <div class="small text-muted"><?= htmlspecialchars($a['description']) ?></div>
                        </div>
                        <span class="ms-2 small"><?= $percent ?>%</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($inProgressAchievements)): ?>
                    <p class="text-muted">No achievements in progress.</p>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="collections" role="tabpanel">
                <p class="text-muted">Coming soon...</p>
            </div>
<div class="tab-pane fade" id="pastes" role="tabpanel">
<?php if (empty($userPastes)): ?>
    <p class="text-muted text-center">No recent pastes found.</p>
<?php else: ?>
    <?php foreach ($userPastes as $paste): ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h5 class="card-title mb-1">
                    <a href="/pages/view.php?id=<?php echo htmlspecialchars($paste['id']); ?>">
                        <?php echo htmlspecialchars($paste['title'] ?: 'Untitled Paste'); ?>
                    </a>
                </h5>
                <span class="badge bg-secondary-subtle text-muted float-end align-self-start">
                    <?php echo htmlspecialchars($paste['language']); ?>
                </span>
            </div>
            <p class="card-text text-muted mb-0">
                <?php
                    $ts = is_numeric($paste['created_at']) ? $paste['created_at'] : strtotime($paste['created_at']);
                    echo date('M j, Y', $ts);
                ?>
            </p>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
    <nav aria-label="Paste pagination">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(buildPageUrl(1)); ?>">First</a>
            </li>
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(buildPageUrl($page - 1)); ?>">Previous</a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?php echo $p == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo htmlspecialchars(buildPageUrl($p)); ?>">
                        <?php echo $p; ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(buildPageUrl($page + 1)); ?>">Next</a>
            </li>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(buildPageUrl($totalPages)); ?>">Last</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    function bindPastePagination() {
        $('#pastes').off('click', '.pagination a').on('click', '.pagination a', function (e) {
            e.preventDefault();
            const url = $(this).attr('href');
            $.get(url, function (data) {
                const newContent = $(data).find('#pastes').html();
                $('#pastes').html(newContent);
                bindPastePagination();
            });
        });
    }
    bindPastePagination();
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

