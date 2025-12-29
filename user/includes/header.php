<!-- Top Header -->
<header class="top-header">
    <button class="sidebar-toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
    <div class="user-dropdown" id="userDropdown">
        <div class="user-dropdown-toggle" onclick="toggleUserDropdown()">
            <div class="user-avatar">
                <?php 
                $name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User';
                echo strtoupper(substr($name, 0, 1)); 
                ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
                <div class="user-role"><?php echo ucfirst($_SESSION['role'] ?? 'User'); ?></div>
            </div>
            <i class="bi bi-chevron-down dropdown-icon"></i>
        </div>
        <div class="user-dropdown-menu">
            <a href="profile.php" class="user-dropdown-item">
                <i class="bi bi-person"></i> My Profile
            </a>
            <a href="settings.php" class="user-dropdown-item">
                <i class="bi bi-gear"></i> Settings
            </a>
            <div class="user-dropdown-divider"></div>
            <a href="../logout.php" class="user-dropdown-item">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</header>


