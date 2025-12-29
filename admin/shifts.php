<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

// Get all shifts
$conn = getDBConnection();
$shifts = $conn->query("SELECT * FROM shifts ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$conn->close();

?>
<?php 
$page_title = 'Shifts';
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
                            <h1 class="h3 mb-0">Shifts</h1>
                            <p class="text-muted">Manage shifts</p>
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#shiftModal" onclick="resetForm()">
                            <i class="bi bi-plus-circle"></i> Add Shift
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
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Grace Minutes</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($shifts)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No shifts found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($shifts as $shift): ?>
                                        <tr>
                                            <td><?php echo $shift['id']; ?></td>
                                            <td><?php echo htmlspecialchars($shift['name']); ?></td>
                                            <td><?php echo date('h:i A', strtotime($shift['start_time'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($shift['end_time'])); ?></td>
                                            <td><?php echo $shift['grace_minutes']; ?> min</td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editShift(<?php echo htmlspecialchars(json_encode($shift)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteShift(<?php echo $shift['id']; ?>)">
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

    <!-- Shift Modal -->
    <div class="modal fade" id="shiftModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="shiftForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="shiftAction" value="create">
                        <input type="hidden" name="id" id="shiftId">
                        
                        <div class="mb-3">
                            <label class="form-label">Shift Name</label>
                            <input type="text" class="form-control" name="name" id="shiftName" placeholder="e.g., Morning Shift">
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" class="form-control" name="start_time" id="shiftStartTime">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" class="form-control" name="end_time" id="shiftEndTime">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Grace Minutes</label>
                            <input type="number" class="form-control" name="grace_minutes" id="shiftGraceMinutes" value="0" min="0" placeholder="e.g., 15">
                            <small class="text-muted">Allowed late minutes before marking as late</small>
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
            const form = document.getElementById('shiftForm');
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

            // Shift Name
            const name = document.getElementById('shiftName').value.trim();
            if (!name) {
                showFieldError('shiftName', 'Shift name is required');
                isValid = false;
            }

            // Start Time
            const startTime = document.getElementById('shiftStartTime').value;
            if (!startTime) {
                showFieldError('shiftStartTime', 'Start time is required');
                isValid = false;
            }

            // End Time
            const endTime = document.getElementById('shiftEndTime').value;
            if (!endTime) {
                showFieldError('shiftEndTime', 'End time is required');
                isValid = false;
            }

            // Validate end time is after start time
            if (startTime && endTime && startTime >= endTime) {
                showFieldError('shiftEndTime', 'End time must be after start time');
                isValid = false;
            }

            return isValid;
        }

        function resetForm() {
            document.getElementById('shiftAction').value = 'create';
            document.getElementById('shiftId').value = '';
            document.getElementById('shiftModal').querySelector('.modal-title').textContent = 'Add Shift';
            document.getElementById('shiftForm').reset();
            clearValidation();
        }
        
        function editShift(shift) {
            document.getElementById('shiftAction').value = 'update';
            document.getElementById('shiftId').value = shift.id;
            document.getElementById('shiftName').value = shift.name || '';
            document.getElementById('shiftStartTime').value = shift.start_time || '';
            document.getElementById('shiftEndTime').value = shift.end_time || '';
            document.getElementById('shiftGraceMinutes').value = shift.grace_minutes || 0;
            document.getElementById('shiftModal').querySelector('.modal-title').textContent = 'Edit Shift';
            clearValidation();
            
            const modal = new bootstrap.Modal(document.getElementById('shiftModal'));
            modal.show();
        }

        function deleteShift(id) {
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

                    fetch('../api/shifts.php', {
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
                            text: 'An error occurred while deleting the shift.',
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
        document.getElementById('shiftForm').addEventListener('submit', function(e) {
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

            fetch('../api/shifts.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('shiftModal'));
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
                    text: 'An error occurred while saving the shift.',
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
        document.getElementById('shiftForm').addEventListener('input', function(e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
                const feedback = e.target.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            }
        });

        // Clear validation on time change
        document.getElementById('shiftForm').addEventListener('change', function(e) {
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

