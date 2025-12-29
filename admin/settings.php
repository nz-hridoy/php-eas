<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

// Load settings from session if available, otherwise use defaults
$settings = $_SESSION['settings'] ?? [];
$weekend_days = $settings['weekend_days'] ?? ['Saturday', 'Sunday'];
$timezone = $settings['timezone'] ?? 'UTC';
$company_name = $settings['company_name'] ?? 'NzCoding';
$company_address = $settings['company_address'] ?? '';
$company_phone = $settings['company_phone'] ?? '';
$company_email = $settings['company_email'] ?? '';

// Common timezones
$timezones = [
    'UTC' => 'UTC',
    'America/New_York' => 'Eastern Time (US)',
    'America/Chicago' => 'Central Time (US)',
    'America/Denver' => 'Mountain Time (US)',
    'America/Los_Angeles' => 'Pacific Time (US)',
    'Europe/London' => 'London',
    'Europe/Paris' => 'Paris',
    'Asia/Dubai' => 'Dubai',
    'Asia/Kolkata' => 'India',
    'Asia/Singapore' => 'Singapore',
    'Asia/Tokyo' => 'Tokyo',
    'Australia/Sydney' => 'Sydney',
];

?>
<?php 
$page_title = 'Settings';
include 'includes/head.php'; 
?>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-gear-fill text-white" style="font-size: 1rem;"></i>
                            </div>
                        </div>
                        <div>
                            <h1 class="h4 mb-0">Settings</h1>
                            <p class="text-muted small mb-0">Configure system settings and preferences</p>
                        </div>
                    </div>
                </div>
            </div>

            <form id="settingsForm">
                <div class="row">
                    <!-- Weekend Days Card -->
                    <div class="col-lg-6 mb-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header d-flex align-items-center py-2">
                                <i class="bi bi-calendar-week me-2 text-primary" style="font-size: 0.95rem;"></i>
                                <h6 class="mb-0">Weekend Days</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">Select the days that are considered weekends (non-working days).</p>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php 
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day): 
                                        $isChecked = in_array($day, $weekend_days);
                                    ?>
                                        <label class="weekend-day-checkbox <?php echo $isChecked ? 'checked' : ''; ?>" for="day_<?php echo strtolower($day); ?>">
                                            <input class="form-check-input" type="checkbox" name="weekend_days[]" value="<?php echo $day; ?>" id="day_<?php echo strtolower($day); ?>" 
                                                <?php echo $isChecked ? 'checked' : ''; ?>>
                                            <span class="day-label"><?php echo $day; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timezone Card -->
                    <div class="col-lg-6 mb-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header d-flex align-items-center py-2">
                                <i class="bi bi-clock me-2 text-primary" style="font-size: 0.95rem;"></i>
                                <h6 class="mb-0">Timezone</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-globe me-1"></i>Select Timezone
                                    </label>
                                    <select class="form-select" name="timezone" id="timezone">
                                        <?php foreach ($timezones as $tz_value => $tz_label): ?>
                                            <option value="<?php echo $tz_value; ?>" <?php echo $timezone === $tz_value ? 'selected' : ''; ?>>
                                                <?php echo $tz_label; ?> (<?php echo $tz_value; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-info-circle me-1"></i>All times will be displayed in the selected timezone.
                                    </small>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Company Information Card -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header d-flex align-items-center py-2">
                        <i class="bi bi-building me-2 text-primary" style="font-size: 0.95rem;"></i>
                        <h6 class="mb-0">Company Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-building me-1"></i>Company Name
                                </label>
                                <input type="text" class="form-control" name="company_name" id="companyName" value="<?php echo htmlspecialchars($company_name); ?>" placeholder="e.g., NzCoding">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-envelope me-1"></i>Company Email
                                </label>
                                <input type="email" class="form-control" name="company_email" id="companyEmail" value="<?php echo htmlspecialchars($company_email); ?>" placeholder="e.g., info@company.com">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-telephone me-1"></i>Company Phone
                                </label>
                                <input type="text" class="form-control" name="company_phone" id="companyPhone" value="<?php echo htmlspecialchars($company_phone); ?>" placeholder="e.g., +1234567890" data-gramm="false">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-geo-alt me-1"></i>Company Address
                                </label>
                                <textarea class="form-control" name="company_address" id="companyAddress" rows="3" placeholder="e.g., 123 Main Street, City, Country" data-gramm="false"><?php echo htmlspecialchars($company_address); ?></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="d-flex justify-content-end mb-3">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-2"></i>Save Settings
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function clearValidation() {
            const form = document.getElementById('settingsForm');
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

            // Timezone
            const timezone = document.getElementById('timezone').value;
            if (!timezone) {
                showFieldError('timezone', 'Timezone is required');
                isValid = false;
            }

            // Company Email (validate format if provided)
            const companyEmail = document.getElementById('companyEmail').value.trim();
            if (companyEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(companyEmail)) {
                showFieldError('companyEmail', 'Please enter a valid email address');
                isValid = false;
            }

            return isValid;
        }

        // Handle form submission
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
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
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            fetch('../api/settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                if (data.success) {
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
                    text: 'An error occurred while saving the settings.',
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
        document.getElementById('settingsForm').addEventListener('input', function(e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
                const feedback = e.target.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            }
        });

        // Clear validation on select change
        document.getElementById('settingsForm').addEventListener('change', function(e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
                const feedback = e.target.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            }
        });

        // Enhance weekend day checkboxes
        document.querySelectorAll('input[name="weekend_days[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const label = this.closest('.weekend-day-checkbox');
                if (this.checked) {
                    label.classList.add('checked');
                } else {
                    label.classList.remove('checked');
                }
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>

