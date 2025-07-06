<?php
session_start();
require_once '../includes/db.php';
require_once '../database/init.php';

$pageTitle = 'User Profile';
include '../includes/header.php';
?>
<main class="container-fluid px-4 py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg">
                <div class="card-header border-0 text-white py-3" style="background-color: #334155;">
                    <h4 class="mb-0"><i class="fas fa-user me-2"></i>User Profile</h4>
                </div>
                <div class="card-body p-4">
                    <p>This page is under construction.</p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
