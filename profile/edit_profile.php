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
                    <form id="profileForm" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="profileImage" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profileImage" accept="image/png, image/jpeg, image/gif">
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

// Show success toast on submit
const form = document.getElementById('profileForm');
form.addEventListener('submit', function(e) {
    e.preventDefault();
    const toast = document.createElement('div');
    toast.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = 'Profile updated successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.body.appendChild(toast);
    setTimeout(() => { if (toast.parentNode) toast.remove(); }, 3000);
});
</script>
<?php include '../includes/footer.php'; ?>
