<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/database/init.php';

$pdo = getDatabase();
$stmt = $pdo->query("SELECT pf.*, p.title FROM paste_flags pf JOIN pastes p ON pf.paste_id = p.id ORDER BY pf.created_at DESC");
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Flagged Pastes';
include __DIR__ . '/includes/header.php';
?>
<main class="container py-5">
    <h1 class="mb-4">Flagged Pastes</h1>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Paste</th>
                <th>Type</th>
                <th>Description</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($flags as $flag): ?>
                <tr>
                    <td><?php echo $flag['id']; ?></td>
                    <td><a href="pages/view.php?id=<?php echo htmlspecialchars($flag['paste_id']); ?>"><?php echo htmlspecialchars($flag['title']); ?></a></td>
                    <td><?php echo htmlspecialchars($flag['flag_type']); ?></td>
                    <td><?php echo htmlspecialchars($flag['reason']); ?></td>
                    <td><?php echo htmlspecialchars($flag['status']); ?></td>
                    <td><?php echo date('Y-m-d H:i', $flag['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
