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
                <div class="card-header border-0 text-white py-3" style="background-color: #334155;">
                    <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create Account</h4>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4 d-none d-md-block">
                        <i class="fas fa-user-plus fa-5x text-primary"></i>
                    </div>
                    <?php if ($success): ?>
                        <div class="alert alert-success">Account created successfully!</div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="register.php" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="username" id="username" placeholder="Username" required>
                            <label for="username">Username *</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" name="email" id="email" placeholder="Email">
                            <label for="email">Email (optional)</label>
                        </div>
                        <div class="form-floating mb-4 position-relative">
                            <input type="password" class="form-control" name="password" id="register-password" placeholder="Password" required>
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" data-target="#register-password">
                                <i class="fas fa-eye"></i>
                            </span>
                            <label for="register-password">Password *</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
