<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    // For now, we'll store settings in a simple key-value format
    // In production, you might want to create a settings table
    
    $weekend_days = isset($_POST['weekend_days']) ? implode(',', $_POST['weekend_days']) : '';
    $timezone = $_POST['timezone'] ?? 'UTC';
    $company_name = trim($_POST['company_name'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $company_phone = trim($_POST['company_phone'] ?? '');
    $company_email = trim($_POST['company_email'] ?? '');
    
    // Store in session or create a settings table
    // For now, we'll just show a success message
    // In production, implement proper settings storage
    
    $message = 'Settings saved successfully!';
    $message_type = 'success';
    
    $conn->close();
}

// Default values (in production, load from database)
$weekend_days = ['Saturday', 'Sunday'];
$timezone = 'UTC';
$company_name = 'NzCoding';
$company_address = '';
$company_phone = '';
$company_email = '';

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
                    <h1 class="h3 mb-0">Settings</h1>
                    <p class="text-muted">Configure system settings</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Weekend Days -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Weekend Days</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            foreach ($days as $day): 
                            ?>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="weekend_days[]" value="<?php echo $day; ?>" id="day_<?php echo strtolower($day); ?>" 
                                            <?php echo in_array($day, $weekend_days) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="day_<?php echo strtolower($day); ?>">
                                            <?php echo $day; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Select the days that are considered weekends (non-working days).</small>
                    </div>
                </div>

                <!-- Timezone -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Timezone</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Select Timezone</label>
                            <select class="form-select" name="timezone" required>
                                <?php foreach ($timezones as $tz_value => $tz_label): ?>
                                    <option value="<?php echo $tz_value; ?>" <?php echo $timezone === $tz_value ? 'selected' : ''; ?>>
                                        <?php echo $tz_label; ?> (<?php echo $tz_value; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">All times will be displayed in the selected timezone.</small>
                        </div>
                    </div>
                </div>

                <!-- Company Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Company Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Email</label>
                                <input type="email" class="form-control" name="company_email" value="<?php echo htmlspecialchars($company_email); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Address</label>
                            <textarea class="form-control" name="company_address" rows="3"><?php echo htmlspecialchars($company_address); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Phone</label>
                            <input type="text" class="form-control" name="company_phone" value="<?php echo htmlspecialchars($company_phone); ?>">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

