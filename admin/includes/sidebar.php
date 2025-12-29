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
        
        <a href="employees.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Employees
        </a>
        <a href="departments.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : ''; ?>">
            <i class="bi bi-building"></i> Departments
        </a>
        <a href="shifts.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'shifts.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock"></i> Shifts
        </a>
        <a href="holidays.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'holidays.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-event"></i> Holidays
        </a>
        <?php 
        $is_reports_page = basename($_SERVER['PHP_SELF']) == 'reports.php';
        // Get report type from URL if on reports page
        if ($is_reports_page) {
            $report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
        } else {
            $report_type = 'daily'; // Default
        }
        ?>
        <div class="sidebar-menu-item <?php echo $is_reports_page ? 'active' : ''; ?>" onclick="toggleSubmenu(this)">
            <i class="bi bi-file-earmark-text"></i> Reports
            <i class="bi bi-chevron-down ms-auto submenu-arrow"></i>
        </div>
        <div class="sidebar-submenu <?php echo $is_reports_page ? 'show' : ''; ?>">
            <a href="reports.php?type=daily" class="sidebar-submenu-item <?php echo $is_reports_page && $report_type === 'daily' ? 'active' : ''; ?>">
                <i class="bi bi-calendar-day"></i> Daily Report
            </a>
            <a href="reports.php?type=monthly" class="sidebar-submenu-item <?php echo $is_reports_page && $report_type === 'monthly' ? 'active' : ''; ?>">
                <i class="bi bi-calendar-month"></i> Monthly Report
            </a>
            <a href="reports.php?type=late-early" class="sidebar-submenu-item <?php echo $is_reports_page && $report_type === 'late-early' ? 'active' : ''; ?>">
                <i class="bi bi-clock-history"></i> Late & Early
            </a>
        </div>
        <a href="settings.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
        <a href="audit-logs.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'audit-logs.php' ? 'active' : ''; ?>">
            <i class="bi bi-journal-text"></i> Audit Logs
        </a>
    </nav>
</aside>

