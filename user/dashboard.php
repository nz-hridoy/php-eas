<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Ensure only employees can access (admin and manager should not access user dashboard)
if ($_SESSION['role'] !== 'employee') {
    header('Location: ../index.php');
    exit();
}

?>
<?php 
$page_title = 'User Dashboard';
include 'includes/head.php'; 
?>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-welcome-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>!</h1>
                    <p class="dashboard-subtitle">Manage your attendance and view your records.</p>
                </div>
                <div class="dashboard-date">
                    <i class="bi bi-calendar3"></i>
                    <span><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>
        </div>

    </main>

    <?php include 'includes/footer.php'; ?>

