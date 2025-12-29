<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

// Get all holidays
$conn = getDBConnection();
$holidays = $conn->query("SELECT * FROM holidays ORDER BY holiday_date DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();

?>
<?php 
$page_title = 'Holidays';
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
                            <h1 class="h3 mb-0">Holidays</h1>
                            <p class="text-muted">Manage holidays</p>
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#holidayModal" onclick="resetForm()">
                            <i class="bi bi-plus-circle"></i> Add Holiday
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
                                    <th>Date</th>
                                    <th>Title</th>
                                    <th>Day</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($holidays)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No holidays found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($holidays as $holiday): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($holiday['holiday_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($holiday['title']); ?></td>
                                            <td><?php echo date('l', strtotime($holiday['holiday_date'])); ?></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editHoliday(<?php echo htmlspecialchars(json_encode($holiday)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteHoliday(<?php echo $holiday['id']; ?>)">
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

    <!-- Holiday Modal -->
    <div class="modal fade" id="holidayModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="holidayForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Holiday</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="holidayAction" value="create">
                        <input type="hidden" name="id" id="holidayId">
                        
                        <div class="mb-3">
                            <label class="form-label">Holiday Date</label>
                            <input type="date" class="form-control" name="holiday_date" id="holidayDate">
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="holidayTitle" placeholder="e.g., New Year's Day">
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
            const form = document.getElementById('holidayForm');
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

            // Holiday Date
            const holidayDate = document.getElementById('holidayDate').value;
            if (!holidayDate) {
                showFieldError('holidayDate', 'Holiday date is required');
                isValid = false;
            }

            // Title
            const title = document.getElementById('holidayTitle').value.trim();
            if (!title) {
                showFieldError('holidayTitle', 'Title is required');
                isValid = false;
            }

            return isValid;
        }

        function resetForm() {
            document.getElementById('holidayAction').value = 'create';
            document.getElementById('holidayId').value = '';
            document.getElementById('holidayModal').querySelector('.modal-title').textContent = 'Add Holiday';
            document.getElementById('holidayForm').reset();
            clearValidation();
        }
        
        function editHoliday(holiday) {
            document.getElementById('holidayAction').value = 'update';
            document.getElementById('holidayId').value = holiday.id;
            document.getElementById('holidayDate').value = holiday.holiday_date || '';
            document.getElementById('holidayTitle').value = holiday.title || '';
            document.getElementById('holidayModal').querySelector('.modal-title').textContent = 'Edit Holiday';
            clearValidation();
            
            const modal = new bootstrap.Modal(document.getElementById('holidayModal'));
            modal.show();
        }

        function deleteHoliday(id) {
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

                    fetch('../api/holidays.php', {
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
                            text: 'An error occurred while deleting the holiday.',
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
        document.getElementById('holidayForm').addEventListener('submit', function(e) {
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

            fetch('../api/holidays.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('holidayModal'));
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
                    text: 'An error occurred while saving the holiday.',
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
        document.getElementById('holidayForm').addEventListener('input', function(e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
                const feedback = e.target.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            }
        });

        // Clear validation on date change
        document.getElementById('holidayForm').addEventListener('change', function(e) {
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

