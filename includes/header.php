<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}
$userData = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username, profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch();
    if ($userData) {
        if (!isset($_SESSION['avatar']) && $userData['profile_image']) {
            $_SESSION['avatar'] = $userData['profile_image'];
        }
        $avatarFile = $_SESSION['avatar'] ?? $userData['profile_image'];
        $userData['avatar'] = $avatarFile
            ? '/uploads/avatars/' . $avatarFile
            : '/img/default-avatar.svg';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'PasteForge'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Prism.js for Syntax Highlighting - Using different theme to avoid visual artifacts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
    
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm fixed-top">
        <div class="container-fluid px-4">
            <!-- Brand -->
            <a class="navbar-brand fw-bold" href="/index.php">
                <i class="fas fa-code me-2"></i>
                PasteForge
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                       <!-- <a class="nav-link" href="/index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/create.php">
                            <i class="fas fa-plus me-1"></i>Create Paste
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/recent.php">
                            <i class="fas fa-clock me-1"></i>Recent Pastes
                        </a>
                    </li>
<?php if ($userData): ?>
                    <li class="nav-item d-lg-none dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="mobileUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="/uploads/avatars/<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'default-avatar.svg'); ?>" width="24" height="24" class="rounded-circle me-2">
                            <?php echo htmlspecialchars($userData['username']); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="mobileUserDropdown">
                            <li>
                                <a class="dropdown-item" href="/profile/edit_profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/profile/profile.php">
                                    <i class="fa fa-user"></i> View Profile
                                </a>
                            </li>
                        </ul>
                    </li>
<?php else: ?>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link" href="/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link" href="/register.php">
                            <i class="fas fa-user-plus me-1"></i>Sign Up
                        </a>
                    </li>
<?php endif; ?>
                </ul>

                <!-- Dark Mode Toggle and Auth Buttons -->
                <div class="d-flex align-items-center ms-auto">
                    <button class="btn btn-outline-light btn-sm me-2" id="themeToggle" type="button">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    <div class="d-none d-lg-flex align-items-center">
<?php if ($userData): ?>
                        <div class="dropdown">
                            <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="/uploads/avatars/<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'default-avatar.svg'); ?>" width="30" height="30" class="rounded-circle me-2">
                                <span><?php echo htmlspecialchars($userData['username']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="/profile/edit_profile.php">
                                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/profile/profile.php">
                                        <i class="fa fa-user"></i> View Profile
                                    </a>
                                </li>
                            </ul>
                        </div>
<?php else: ?>
                        <a href="/login.php" class="nav-link me-2"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="/register.php" class="nav-link"><i class="fas fa-user-plus"></i> Sign Up</a>
<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
