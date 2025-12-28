<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}


?>
<?php 
$page_title = 'Admin Dashboard';
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
                    <h1 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>!</h1>
                    <p class="dashboard-subtitle">Here's what's happening with your attendance system today.</p>
                </div>
                <div class="dashboard-date">
                    <i class="bi bi-calendar3"></i>
                    <span><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

