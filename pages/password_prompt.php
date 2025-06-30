<?php
session_start();
require_once '../includes/db.php';

$pasteId = $_GET['id'] ?? ($_POST['id'] ?? '');
if (empty($pasteId)) {
    die('Invalid request');
}

$paste = getPasteById($pasteId);
if (!$paste) {
    die('Paste not found');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $valid = password_verify($password, $paste['password']);
    logPasteAccessAttempt($pasteId, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $valid);
    if ($valid) {
        $_SESSION['unlocked'][$pasteId] = time() + 1800; // 30 min access
        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: view.php?id=' . $pasteId);
        exit;
    } else {
        $error = 'Incorrect password. Please try again.';
        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
}

$pageTitle = 'Password Required';
include '../includes/header.php';
?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Enter Password</h5>
                    <?php if ($error): ?>
                        <div class="alert alert-danger" id="errorMsg"><?php echo htmlspecialchars($error); ?></div>
                    <?php else: ?>
                        <div class="alert alert-info" id="errorMsg" style="display:none"></div>
                    <?php endif; ?>
                    <form method="post" id="passwordForm">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($pasteId); ?>">
                        <div class="alert alert-info text-light bg-opacity-50 rounded shadow-sm">
                            🔒 You are trying to view a password-protected paste. A password is required to proceed.
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary">Unlock</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
document.getElementById('passwordForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('ajax','1');
    fetch('', {method:'POST', body:formData})
        .then(r => r.json())
        .then(data => {
            if(data.success){
                window.location.href = 'view.php?id=<?php echo htmlspecialchars($pasteId); ?>';
            } else if(data.error){
                const msg=document.getElementById('errorMsg');
                msg.textContent = data.error;
                msg.style.display='block';
                msg.classList.add('alert','alert-danger');
            }
        });
});
</script>
<?php include '../includes/footer.php'; ?>
