<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

// Get all departments
$conn = getDBConnection();
$departments = $conn->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$conn->close();

?>
<?php 
$page_title = 'Departments';
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
                            <h1 class="h3 mb-0">Departments</h1>
                            <p class="text-muted">Manage departments</p>
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#departmentModal" onclick="resetForm()">
                            <i class="bi bi-plus-circle"></i> Add Department
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
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Created At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($departments)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No departments found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <tr>
                                            <td><?php echo $dept['id']; ?></td>
                                            <td><?php echo htmlspecialchars($dept['name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($dept['created_at'])); ?></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editDepartment(<?php echo htmlspecialchars(json_encode($dept)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteDepartment(<?php echo $dept['id']; ?>)">
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

    <!-- Department Modal -->
    <div class="modal fade" id="departmentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="departmentForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="departmentAction" value="create">
                        <input type="hidden" name="id" id="departmentId">
                        
                        <div class="mb-3">
                            <label class="form-label">Department Name</label>
                            <input type="text" class="form-control" name="name" id="departmentName" placeholder="e.g., Information Technology">
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
            const form = document.getElementById('departmentForm');
            const inputs = form.querySelectorAll('.form-control');
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

            // Department Name
            const name = document.getElementById('departmentName').value.trim();
            if (!name) {
                showFieldError('departmentName', 'Department name is required');
                isValid = false;
            }

            return isValid;
        }

        function resetForm() {
            document.getElementById('departmentAction').value = 'create';
            document.getElementById('departmentId').value = '';
            document.getElementById('departmentModal').querySelector('.modal-title').textContent = 'Add Department';
            document.getElementById('departmentForm').reset();
            clearValidation();
        }
        
        function editDepartment(dept) {
            document.getElementById('departmentAction').value = 'update';
            document.getElementById('departmentId').value = dept.id;
            document.getElementById('departmentName').value = dept.name || '';
            document.getElementById('departmentModal').querySelector('.modal-title').textContent = 'Edit Department';
            clearValidation();
            
            const modal = new bootstrap.Modal(document.getElementById('departmentModal'));
            modal.show();
        }

        function deleteDepartment(id) {
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

                    fetch('../api/departments.php', {
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
                            text: 'An error occurred while deleting the department.',
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
        document.getElementById('departmentForm').addEventListener('submit', function(e) {
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

            fetch('../api/departments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('departmentModal'));
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
                    text: 'An error occurred while saving the department.',
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
        document.getElementById('departmentForm').addEventListener('input', function(e) {
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

