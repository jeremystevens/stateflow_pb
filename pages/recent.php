<?php
require_once '../includes/db.php';
require_once '../database/init.php';

// Pagination - limit to 6 pastes per page for mobile-friendly card grid layout
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6; // 3 cards per row, 2 rows max for better mobile viewing
$offset = ($page - 1) * $perPage;

// Get recent pastes with pagination
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM pastes 
        WHERE visibility = 'public' AND (expire_time IS NULL OR expire_time > ?)
    ");
    $stmt->execute([time()]);
    $totalPastes = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT id, title, language, created_at, views, password
        FROM pastes 
        WHERE visibility = 'public' AND (expire_time IS NULL OR expire_time > ?)
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([time(), $perPage, $offset]);
    $pastes = $stmt->fetchAll();
    
    $totalPages = ceil($totalPastes / $perPage);
} catch (PDOException $e) {
    error_log("Recent pastes query failed: " . $e->getMessage());
    $pastes = [];
    $totalPastes = 0;
    $totalPages = 0;
}

$pageTitle = "Recent Pastes";
include '../includes/header.php';
?>

<main class="container-fluid px-4 py-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-clock me-2 text-primary"></i>Recent Pastes
                    </h2>
                    <p class="text-muted mb-0">
                        <?php echo number_format($totalPastes); ?> pastes available
                    </p>
                </div>
                <div>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create New Paste
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($pastes)): ?>
    <!-- Empty State -->
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-clipboard fa-3x text-muted"></i>
                    </div>
                    <h4 class="mb-3">No Pastes Found</h4>
                    <p class="text-muted mb-4">
                        There are no recent pastes to display. Be the first to create one!
                    </p>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create First Paste
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Pastes Grid -->
    <div class="pastes-grid">
        <?php foreach ($pastes as $paste): ?>
            <?php
            // Calculate time ago
            $createdTime = is_numeric($paste['created_at']) ? $paste['created_at'] : strtotime($paste['created_at']);
            $currentTime = time();
            $timeDiff = $currentTime - $createdTime;
            
            if ($timeDiff < 60) {
                $timeAgo = 'just now';
            } elseif ($timeDiff < 3600) {
                $minutes = floor($timeDiff / 60);
                $timeAgo = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
            } elseif ($timeDiff < 86400) {
                $hours = floor($timeDiff / 3600);
                $timeAgo = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
            } else {
                $days = floor($timeDiff / 86400);
                $timeAgo = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
            }
            ?>
            <a href="view.php?id=<?php echo htmlspecialchars($paste['id']); ?>" class="paste-card paste-card-link">
                <p class="paste-language"><?php echo htmlspecialchars($paste['language']); ?></p>
                <div class="paste-title">
                    <?php echo htmlspecialchars($paste['title'] ?: 'Untitled Paste'); ?>
                    <?php if (!empty($paste['password'])): ?>
                        <i class="fa fa-lock text-muted ms-2" title="Password Protected"></i>
                    <?php endif; ?>
                </div>
                <p class="paste-time"><?php echo $timeAgo; ?></p>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-container">
        <div class="pagination-wrapper">
            <!-- Previous Page -->
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn pagination-prev">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-prev disabled">
                    <i class="fas fa-chevron-left"></i>
                </span>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i === $page) {
                    echo "<span class='pagination-btn pagination-current'>$i</span>";
                } else {
                    echo "<a href='?page=$i' class='pagination-btn'>$i</a>";
                }
            }
            ?>

            <!-- Next Page -->
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn pagination-next">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-next disabled">
                    <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
