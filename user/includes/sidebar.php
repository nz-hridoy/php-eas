<!-- Sidebar Overlay (Mobile Only) -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">NzCoding</a>
        <button class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </nav>
</aside>


