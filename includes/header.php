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
    
    <!-- Vanta.js Background Animation Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r121/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@0.5.24/dist/vanta.cells.min.js"></script>
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div id="vanta-bg">
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
                        <a class="nav-link" href="/index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
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
                </ul>

                <!-- Dark Mode Toggle -->
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-light btn-sm me-2" id="themeToggle" type="button">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
