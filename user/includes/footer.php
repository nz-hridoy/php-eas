<footer class="text-center" style="margin-left: 260px;">
    <p class="text-muted mb-0">Made with <span class="heart">❤️</span> by nzcoding</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/autocomplete-off.js"></script>
<script>
    function toggleUserDropdown() {
        document.getElementById('userDropdown').classList.toggle('show');
    }

    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // On mobile, toggle 'show' class
            sidebar.classList.toggle('show');
        } else {
            // On desktop, toggle 'collapsed' class
            sidebar.classList.toggle('collapsed');
            
            // Save sidebar state to localStorage (desktop only)
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }
    }

    // Restore sidebar state on page load (desktop only)
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar');
        const isMobile = window.innerWidth <= 768;
        
        if (!isMobile) {
            // Desktop: restore collapsed state from localStorage
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
            // Remove show class on desktop
            sidebar.classList.remove('show');
        } else {
            // Mobile: always start with sidebar hidden
            sidebar.classList.remove('show');
            sidebar.classList.remove('collapsed');
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.querySelector('.sidebar');
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // On mobile, remove collapsed class and ensure sidebar is hidden
            sidebar.classList.remove('collapsed');
            // If sidebar is showing, keep it; otherwise ensure it's hidden
            if (!sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        } else {
            // On desktop, remove show class and use collapsed class
            sidebar.classList.remove('show');
            // Restore collapsed state from localStorage
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        }
    });

    // Close sidebar when clicking overlay on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile && overlay && event.target === overlay) {
            sidebar.classList.remove('show');
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdown');
        if (!dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
</script>
</body>
</html>


