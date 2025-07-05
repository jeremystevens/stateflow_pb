<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'database/init.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Incorrect username or password, please try again.';
        }
    }
}

$pageTitle = 'Login';
include 'includes/header.php';
?>
<main class="container-fluid px-4 py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg">
                <div class="card-header border-0 text-white py-3" style="background-color: #334155;">
                    <h4 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Login</h4>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4 d-none d-md-block">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <?php if ($error): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="login.php" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="username" id="username" placeholder="Username" required>
                            <label for="username">Username *</label>
                        </div>
                        <div class="form-floating mb-4 position-relative">
                            <input type="password" class="form-control" name="password" id="login-password" placeholder="Password" required>
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" data-target="#login-password">
                                <i class="fas fa-eye"></i>
                            </span>
                            <label for="login-password">Password *</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
