<?php
session_start();
require_once 'includes/db.php';
require_once 'database/init.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordRaw = trim($_POST['password'] ?? '');

    if ($username === '' || $passwordRaw === '') {
        $error = 'Username and password are required.';
    } else {
        $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
        $id = bin2hex(random_bytes(16));

        try {
            $stmt = $pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $username, $email ?: null, $password]);
            $success = true;
        } catch (PDOException $e) {
            $error = 'Registration failed. Username may already exist.';
        }
    }
}

$pageTitle = 'Sign Up';
include 'includes/header.php';
?>

<main class="container-fluid px-4 py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg">
                <div class="card-header border-0 bg-primary text-white py-3">
                    <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create Account</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success">Account created successfully!</div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="register.php" class="needs-validation" novalidate>
                        <div class="mb-3 form-group">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" id="username" required>
                        </div>
                        <div class="mb-3 form-group">
                            <label for="email" class="form-label">Email (optional)</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                        <div class="mb-3 form-group">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" id="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
