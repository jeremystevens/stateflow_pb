<?php
session_start();
require_once '../includes/db.php';
require_once '../database/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$stmt = $pdo->prepare("SELECT username, profile_image, tagline, website FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$avatar = $user['profile_image'] ?: '/img/default-avatar.svg';
$tagline = $user['tagline'] ?? '';
$website = $user['website'] ?? '';

$pageTitle = 'Edit Profile';
include '../includes/header.php';
?>
<main class="container-fluid px-4 py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg">
                <div class="card-header border-0 text-white py-3" style="background-color: #334155;">
                    <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile</h4>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <img id="avatarPreview" src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="rounded-circle" width="120" height="120">
                    </div>
                    <form id="profileForm" action="/profile/save_profile.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="profileImage" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profileImage" name="avatar" accept="image/png, image/jpeg, image/gif">
                            <div class="form-text">JPG, PNG or GIF only.</div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="tagline" name="tagline" placeholder="Tagline" maxlength="100" value="<?php echo htmlspecialchars($tagline); ?>">
                            <label for="tagline">Tagline</label>
                            <div class="form-text">Max 100 characters.</div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="url" class="form-control" id="website" name="website" placeholder="https://example.com" value="<?php echo htmlspecialchars($website); ?>">
                            <label for="website">Website</label>
                        </div>
                        <div id="alertBox" class="alert d-none" role="alert"></div>
                        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
document.getElementById('profileImage').addEventListener('change', function(e) {
    const [file] = this.files;
    if (file) {
        const preview = document.getElementById('avatarPreview');
        preview.src = URL.createObjectURL(file);
    }
});

const form = document.getElementById('profileForm');
const alertBox = document.getElementById('alertBox');
form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    fetch('/profile/save_profile.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        alertBox.classList.remove('d-none');
        alertBox.classList.remove('alert-success', 'alert-danger');
        alertBox.classList.add('alert-' + (data.success ? 'success' : 'danger'));
        alertBox.textContent = data.message;
        submitBtn.innerHTML = 'Save Changes';
        submitBtn.disabled = false;
    })
    .catch(() => {
        alertBox.classList.remove('d-none');
        alertBox.classList.remove('alert-success', 'alert-danger');
        alertBox.classList.add('alert-danger');
        alertBox.textContent = 'An unexpected error occurred.';
        submitBtn.innerHTML = 'Save Changes';
        submitBtn.disabled = false;
    });
});
</script>
<?php include '../includes/footer.php'; ?>
