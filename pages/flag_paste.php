<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../database/init.php';

$pdo = getDatabase();

// Create table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS paste_flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    paste_id INTEGER NOT NULL,
    user_id TEXT,
    ip_address TEXT,
    flag_type TEXT NOT NULL,
    reason TEXT,
    description TEXT,
    created_at INTEGER DEFAULT (strftime('%s', 'now')),
    status TEXT DEFAULT 'pending',
    reviewed_by TEXT,
    reviewed_at INTEGER,
    FOREIGN KEY(paste_id) REFERENCES pastes(id),
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

$categories = [
    ['spam', 'Spam or unwanted promotional content', 2, 0],
    ['offensive', 'Offensive, hateful, or inappropriate content', 3, 1],
    ['malware', 'Contains malicious code or viruses', 4, 1],
    ['phishing', 'Phishing or scam attempt', 4, 1],
    ['copyright', 'Copyright infringement', 3, 0],
    ['personal_info', 'Contains personal or private information', 3, 1],
    ['illegal', 'Illegal content or activities', 4, 1],
    ['other', 'Other reason (please specify)', 1, 0]
];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $pasteId = $_GET['paste_id'] ?? '';
    ob_start();
    ?>
    <div class="modal fade" id="flagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Report Paste</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="flagForm">
                    <div class="modal-body">
                        <div class="mb-2">Select a reason:</div>
                        <?php foreach ($categories as $cat): list($val,$label,$sev,$needDesc) = $cat; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="flag_type" id="flag_<?php echo $val; ?>" value="<?php echo $val; ?>" required>
                                <label class="form-check-label" for="flag_<?php echo $val; ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                    <span class="badge severity-<?php echo $sev; ?> ms-2"><?php echo $sev >=4 ? 'HIGH' : ($sev==3 ? 'MED' : 'LOW'); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <textarea class="form-control mt-3" name="description" placeholder="Additional context" rows="3"></textarea>
                        <div class="form-text text-warning mt-2">False reports may result in penalties.</div>
                        <div class="text-danger small mt-2 error-message"></div>
                        <input type="hidden" name="paste_id" value="<?php echo htmlspecialchars($pasteId); ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger">Submit</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    echo ob_get_clean();
    exit;
}

// POST handling
$paste_id = $_POST['paste_id'] ?? '';
$flag_type = $_POST['flag_type'] ?? '';
$reason = $_POST['reason'] ?? '';
$description = trim($_POST['description'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$response = ['success' => false];
if (!$paste_id || !$flag_type) {
    $response['message'] = 'Missing required fields';
    echo json_encode($response);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM pastes WHERE id = ?');
$stmt->execute([$paste_id]);
$paste = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$paste) {
    $response['message'] = 'Paste not found';
    echo json_encode($response);
    exit;
}

if ($user_id && $paste['user_id'] && $paste['user_id'] === $user_id) {
    $response['message'] = 'You cannot report your own paste';
    echo json_encode($response);
    exit;
}

$dupStmt = $pdo->prepare('SELECT COUNT(*) FROM paste_flags WHERE paste_id = ? AND (user_id = ? OR ip_address = ?) AND flag_type = ?');
$dupStmt->execute([$paste_id, $user_id, $ip, $flag_type]);
if ($dupStmt->fetchColumn() > 0) {
    $response['message'] = 'You have already reported this paste';
    echo json_encode($response);
    exit;
}

$insert = $pdo->prepare('INSERT INTO paste_flags (paste_id, user_id, ip_address, flag_type, reason, description) VALUES (?, ?, ?, ?, ?, ?)');
$insert->execute([$paste_id, $user_id, $ip, $flag_type, $reason, $description]);

$update = $pdo->prepare('UPDATE pastes SET flags = flags + 1 WHERE id = ?');
$update->execute([$paste_id]);

$response['success'] = true;
$response['message'] = 'Report submitted successfully';

echo json_encode($response);
