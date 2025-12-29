<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

// Get all employees
$conn = getDBConnection();
$employees = $conn->query("
    SELECT u.*, d.name as department_name, s.name as shift_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    LEFT JOIN shifts s ON u.shift_id = s.id 
    WHERE u.role IN ('employee', 'manager')
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get departments and shifts for dropdowns
$departments = $conn->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$shifts = $conn->query("SELECT * FROM shifts ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$conn->close();

?>
<?php 
$page_title = 'Employees';
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
                            <h1 class="h3 mb-0">Employees</h1>
                            <p class="text-muted">Manage employees</p>
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal" onclick="resetForm()">
                            <i class="bi bi-plus-circle"></i> Add Employee
                        </button>
                    </div>
                </div>
            </div>


            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Shift</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No employees found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($emp['employee_code'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['department_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($emp['shift_name'] ?? 'N/A'); ?></td>
                                            <td><span class="badge bg-info"><?php echo ucfirst($emp['role']); ?></span></td>
                                            <td><span class="badge bg-<?php echo $emp['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($emp['status']); ?></span></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($emp)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteEmployee(<?php echo $emp['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Employee Modal -->
    <div class="modal fade" id="employeeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="employeeForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="employeeAction" value="create">
                        <input type="hidden" name="id" id="employeeId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee Code</label>
                                <input type="text" class="form-control" name="employee_code" id="employeeCode" placeholder="e.g., EMP001">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="name" id="employeeName" placeholder="e.g., John Doe">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="employeeEmail" placeholder="e.g., john.doe@example.com">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <small class="text-muted">(leave blank to keep current)</small></label>
                                <input type="password" class="form-control" name="password" id="employeePassword" placeholder="Enter new password">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="employeePhone" placeholder="e.g., +1234567890">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Join Date</label>
                                <input type="date" class="form-control" name="join_date" id="employeeJoinDate">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" id="employeeRole">
                                    <option value="">Select Role</option>
                                    <option value="employee">Employee</option>
                                    <option value="manager">Manager</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department_id" id="employeeDepartment">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Shift</label>
                                <select class="form-select" name="shift_id" id="employeeShift">
                                    <option value="">Select Shift</option>
                                    <?php foreach ($shifts as $shift): ?>
                                        <option value="<?php echo $shift['id']; ?>"><?php echo htmlspecialchars($shift['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="employeeStatus">
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function clearValidation() {
            const form = document.getElementById('employeeForm');
            const inputs = form.querySelectorAll('.form-control, .form-select');
            inputs.forEach(input => {
                input.classList.remove('is-invalid');
                const feedback = input.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            });
        }

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            field.classList.add('is-invalid');
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = message;
            }
        }

        function validateForm() {
            clearValidation();
            let isValid = true;
            const action = document.getElementById('employeeAction').value;

            // Employee Code
            const employeeCode = document.getElementById('employeeCode').value.trim();
            if (!employeeCode) {
                showFieldError('employeeCode', 'Employee code is required');
                isValid = false;
            }

            // Full Name
            const name = document.getElementById('employeeName').value.trim();
            if (!name) {
                showFieldError('employeeName', 'Full name is required');
                isValid = false;
            }

            // Email
            const email = document.getElementById('employeeEmail').value.trim();
            if (!email) {
                showFieldError('employeeEmail', 'Email is required');
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError('employeeEmail', 'Please enter a valid email address');
                isValid = false;
            }

            // Password (required only for create)
            if (action === 'create') {
                const password = document.getElementById('employeePassword').value;
                if (!password) {
                    showFieldError('employeePassword', 'Password is required for new employees');
                    isValid = false;
                } else if (password.length < 6) {
                    showFieldError('employeePassword', 'Password must be at least 6 characters');
                    isValid = false;
                }
            }

            // Role
            const role = document.getElementById('employeeRole').value;
            if (!role) {
                showFieldError('employeeRole', 'Role is required');
                isValid = false;
            }

            // Status
            const status = document.getElementById('employeeStatus').value;
            if (!status) {
                showFieldError('employeeStatus', 'Status is required');
                isValid = false;
            }

            return isValid;
        }

        function resetForm() {
            document.getElementById('employeeAction').value = 'create';
            document.getElementById('employeeId').value = '';
            document.getElementById('employeeModal').querySelector('.modal-title').textContent = 'Add Employee';
            document.getElementById('employeeForm').reset();
            clearValidation();
        }
        
        function editEmployee(emp) {
            document.getElementById('employeeAction').value = 'update';
            document.getElementById('employeeId').value = emp.id;
            document.getElementById('employeeCode').value = emp.employee_code || '';
            document.getElementById('employeeName').value = emp.name || '';
            document.getElementById('employeeEmail').value = emp.email || '';
            document.getElementById('employeePhone').value = emp.phone || '';
            document.getElementById('employeeJoinDate').value = emp.join_date || '';
            document.getElementById('employeeRole').value = emp.role || '';
            document.getElementById('employeeDepartment').value = emp.department_id || '';
            document.getElementById('employeeShift').value = emp.shift_id || '';
            document.getElementById('employeeStatus').value = emp.status || '';
            document.getElementById('employeeModal').querySelector('.modal-title').textContent = 'Edit Employee';
            clearValidation();
            
            const modal = new bootstrap.Modal(document.getElementById('employeeModal'));
            modal.show();
        }

        function deleteEmployee(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                width: 400,
                customClass: {
                    popup: 'small-swal'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    fetch('../api/employees.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                width: 400,
                                customClass: {
                                    popup: 'small-swal'
                                }
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'OK',
                                width: 400,
                                customClass: {
                                    popup: 'small-swal'
                                }
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while deleting the employee.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            width: 400,
                            customClass: {
                                popup: 'small-swal'
                            }
                        });
                        console.error('Error:', error);
                    });
                }
            });
        }

        // Handle form submission
        document.getElementById('employeeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                // Scroll to first error
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                return;
            }
            
            const formData = new FormData(this);
            const action = formData.get('action');
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            fetch('../api/employees.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('employeeModal'));
                    modal.hide();
                    
                    const actionText = action === 'create' ? 'created' : 'updated';
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6',
                        width: 400,
                        customClass: {
                            popup: 'small-swal'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        width: 400,
                        customClass: {
                            popup: 'small-swal'
                        }
                    });
                }
            })
            .catch(error => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while saving the employee.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    width: 400,
                    customClass: {
                        popup: 'small-swal'
                    }
                });
                console.error('Error:', error);
            });
        });

        // Clear validation on input
        document.getElementById('employeeForm').addEventListener('input', function(e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
                const feedback = e.target.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            }
        });

        // Clear validation on select change
        document.getElementById('employeeForm').addEventListener('change', function(e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
                const feedback = e.target.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            }
        });
    </script>

    <?php include 'includes/footer.php'; ?>

