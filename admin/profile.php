<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get user profile
$stmt = $conn->prepare("
    SELECT u.*, d.name as department_name, s.name as shift_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    LEFT JOIN shifts s ON u.shift_id = s.id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

?>
<?php 
$page_title = 'My Profile';
include 'includes/head.php'; 
?>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h1 class="h3 mb-0">My Profile</h1>
                            <p class="text-muted">Manage your profile information</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="bi bi-person me-2"></i>Profile Information
                            </h5>
                            
                            <form id="profileForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-person me-1"></i>Full Name
                                        </label>
                                        <input type="text" class="form-control" name="name" id="profileName" value="<?php echo htmlspecialchars($user['name']); ?>" placeholder="Enter your full name">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-envelope me-1"></i>Email
                                        </label>
                                        <input type="email" class="form-control" name="email" id="profileEmail" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Enter your email">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-telephone me-1"></i>Phone
                                        </label>
                                        <input type="text" class="form-control" name="phone" id="profilePhone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter your phone number">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-lock me-1"></i>New Password
                                        </label>
                                        <input type="password" class="form-control" name="password" id="profilePassword" placeholder="Leave blank to keep current password">
                                        <div class="invalid-feedback"></div>
                                        <small class="text-muted">Leave blank if you don't want to change your password</small>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary" id="saveBtn">
                                        <i class="bi bi-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h6 class="card-title mb-3">
                                <i class="bi bi-info-circle me-2"></i>Account Information
                            </h6>
                            
                            <div class="mb-3">
                                <div class="text-muted small mb-1">Role</div>
                                <div class="fw-bold">
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'info'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($user['employee_code']): ?>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Employee Code</div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($user['employee_code']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($user['department_name']): ?>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Department</div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($user['department_name']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($user['shift_name']): ?>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Shift</div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($user['shift_name']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($user['join_date']): ?>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Join Date</div>
                                    <div class="fw-bold"><?php echo date('M d, Y', strtotime($user['join_date'])); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <div class="text-muted small mb-1">Status</div>
                                <div>
                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($user['last_login_at']): ?>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Last Login</div>
                                    <div class="fw-bold"><?php echo date('M d, Y h:i A', strtotime($user['last_login_at'])); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <div class="text-muted small mb-1">Member Since</div>
                                <div class="fw-bold"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const btn = document.getElementById('saveBtn');
            const originalText = btn.innerHTML;
            
            // Remove previous validation classes
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(el => {
                el.textContent = '';
            });
            
            // Disable button and show loading
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            
            const formData = new FormData(form);
            
            fetch('../api/profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#2D3748',
                        background: '#2D3748',
                        color: '#FFFFFF',
                        customClass: {
                            popup: 'swal2-popup',
                            title: 'swal2-title',
                            content: 'swal2-content'
                        }
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#2D3748',
                        background: '#2D3748',
                        color: '#FFFFFF',
                        customClass: {
                            popup: 'swal2-popup',
                            title: 'swal2-title',
                            content: 'swal2-content'
                        }
                    });
                }
                btn.disabled = false;
                btn.innerHTML = originalText;
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#2D3748',
                    background: '#2D3748',
                    color: '#FFFFFF',
                    customClass: {
                        popup: 'swal2-popup',
                        title: 'swal2-title',
                        content: 'swal2-content'
                    }
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    </script>

