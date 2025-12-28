<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Ensure only employees can access
if ($_SESSION['role'] !== 'employee') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$message = '';
$message_type = '';

// Handle check-in/check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $conn = getDBConnection();
    
    // Get user's shift
    $userStmt = $conn->prepare("SELECT shift_id, department_id FROM users WHERE id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();
    
    if (!$user || !$user['shift_id']) {
        $message = 'Your account is not properly configured. Please contact administrator.';
        $message_type = 'danger';
    } else {
        // Check if attendance record exists for today
        $checkStmt = $conn->prepare("SELECT * FROM attendance_records WHERE user_id = ? AND att_date = ?");
        $checkStmt->bind_param("is", $user_id, $today);
        $checkStmt->execute();
        $attendance = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        $current_time = date('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if ($action === 'check_in') {
            if ($attendance && $attendance['check_in_time']) {
                $message = 'You have already checked in today.';
                $message_type = 'warning';
            } else {
                // Get shift details for late calculation
                $shiftStmt = $conn->prepare("SELECT start_time, grace_minutes FROM shifts WHERE id = ?");
                $shiftStmt->bind_param("i", $user['shift_id']);
                $shiftStmt->execute();
                $shift = $shiftStmt->get_result()->fetch_assoc();
                $shiftStmt->close();
                
                $shift_start = strtotime($today . ' ' . $shift['start_time']);
                $check_in_time = strtotime($current_time);
                $late_minutes = max(0, floor(($check_in_time - $shift_start) / 60) - $shift['grace_minutes']);
                
                if ($attendance) {
                    // Update existing record
                    $updateStmt = $conn->prepare("UPDATE attendance_records SET check_in_time = ?, check_in_ip = ?, late_minutes = ?, status = ? WHERE id = ?");
                    $status = $late_minutes > 0 ? 'late' : 'present';
                    $updateStmt->bind_param("ssisi", $current_time, $ip_address, $late_minutes, $status, $attendance['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    // Create new record
                    $insertStmt = $conn->prepare("INSERT INTO attendance_records (user_id, att_date, shift_id, check_in_time, check_in_ip, late_minutes, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $status = $late_minutes > 0 ? 'late' : 'present';
                    $insertStmt->bind_param("isissis", $user_id, $today, $user['shift_id'], $current_time, $ip_address, $late_minutes, $status);
                    $insertStmt->execute();
                    $insertStmt->close();
                }
                
                $message = 'Check-in successful!';
                $message_type = 'success';
            }
        } elseif ($action === 'check_out') {
            if (!$attendance || !$attendance['check_in_time']) {
                $message = 'Please check in first.';
                $message_type = 'warning';
            } elseif ($attendance['check_out_time']) {
                $message = 'You have already checked out today.';
                $message_type = 'warning';
            } else {
                // Get shift details for early calculation
                $shiftStmt = $conn->prepare("SELECT end_time FROM shifts WHERE id = ?");
                $shiftStmt->bind_param("i", $user['shift_id']);
                $shiftStmt->execute();
                $shift = $shiftStmt->get_result()->fetch_assoc();
                $shiftStmt->close();
                
                $shift_end = strtotime($today . ' ' . $shift['end_time']);
                $check_out_time = strtotime($current_time);
                $early_minutes = max(0, floor(($shift_end - $check_out_time) / 60));
                
                // Calculate work minutes
                $check_in = strtotime($attendance['check_in_time']);
                $work_minutes = max(0, floor(($check_out_time - $check_in) / 60));
                
                $updateStmt = $conn->prepare("UPDATE attendance_records SET check_out_time = ?, check_out_ip = ?, early_minutes = ?, work_minutes = ? WHERE id = ?");
                $updateStmt->bind_param("ssiii", $current_time, $ip_address, $early_minutes, $work_minutes, $attendance['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                $message = 'Check-out successful!';
                $message_type = 'success';
            }
        }
    }
    
    $conn->close();
}

// Get today's attendance record
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT ar.*, s.start_time, s.end_time FROM attendance_records ar LEFT JOIN shifts s ON ar.shift_id = s.id WHERE ar.user_id = ? AND ar.att_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user's shift info
$shiftStmt = $conn->prepare("SELECT s.* FROM shifts s INNER JOIN users u ON s.id = u.shift_id WHERE u.id = ?");
$shiftStmt->bind_param("i", $user_id);
$shiftStmt->execute();
$shift_info = $shiftStmt->get_result()->fetch_assoc();
$shiftStmt->close();
$conn->close();

?>
<?php 
$page_title = 'Today\'s Attendance';
include 'includes/head.php'; 
?>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h1 class="h3 mb-1">Today's Attendance</h1>
                            <p class="text-muted mb-0"><?php echo date('l, F j, Y'); ?></p>
                        </div>
                        <?php if ($shift_info): ?>
                            <div class="text-end">
                                <div class="badge bg-info fs-6 px-3 py-2">
                                    <i class="bi bi-clock"></i> <?php echo htmlspecialchars($shift_info['name']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Check In/Out Section -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="bi bi-calendar-check me-2"></i>Check In / Check Out
                            </h5>
                            
                            <?php if ($shift_info): ?>
                                <div class="mb-4 p-3 rounded" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="mb-2">
                                                <i class="bi bi-sunrise text-warning fs-4"></i>
                                            </div>
                                            <div class="text-muted small">Shift Start</div>
                                            <div class="fw-bold"><?php echo date('h:i A', strtotime($shift_info['start_time'])); ?></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-2">
                                                <i class="bi bi-sunset text-info fs-4"></i>
                                            </div>
                                            <div class="text-muted small">Shift End</div>
                                            <div class="fw-bold"><?php echo date('h:i A', strtotime($shift_info['end_time'])); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Check In Section -->
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Check In
                                    </h6>
                                    <?php if ($today_attendance && $today_attendance['check_in_time']): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pending</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!$today_attendance || !$today_attendance['check_in_time']): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="check_in">
                                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3">
                                            <i class="bi bi-box-arrow-in-right me-2"></i>Check In Now
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="p-3 rounded d-flex align-items-center justify-content-between" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-check-lg text-white fs-5"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold">Checked In</div>
                                                <div class="text-muted small">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo date('h:i A', strtotime($today_attendance['check_in_time'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($today_attendance['late_minutes'] > 0): ?>
                                            <span class="badge bg-warning fs-6 px-3 py-2">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                <?php echo $today_attendance['late_minutes']; ?> min late
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success fs-6 px-3 py-2">
                                                <i class="bi bi-check-circle me-1"></i>On Time
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Check Out Section -->
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0">
                                        <i class="bi bi-box-arrow-right me-2"></i>Check Out
                                    </h6>
                                    <?php if ($today_attendance && $today_attendance['check_out_time']): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif ($today_attendance && $today_attendance['check_in_time']): ?>
                                        <span class="badge bg-warning">Ready</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Available</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($today_attendance && $today_attendance['check_in_time'] && !$today_attendance['check_out_time']): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="check_out">
                                        <button type="submit" class="btn btn-danger btn-lg w-100 py-3">
                                            <i class="bi bi-box-arrow-right me-2"></i>Check Out Now
                                        </button>
                                    </form>
                                <?php elseif ($today_attendance && $today_attendance['check_out_time']): ?>
                                    <div class="p-3 rounded d-flex align-items-center justify-content-between" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-check-lg text-white fs-5"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold">Checked Out</div>
                                                <div class="text-muted small">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo date('h:i A', strtotime($today_attendance['check_out_time'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($today_attendance['early_minutes'] > 0): ?>
                                            <span class="badge bg-warning fs-6 px-3 py-2">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                <?php echo $today_attendance['early_minutes']; ?> min early
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success fs-6 px-3 py-2">
                                                <i class="bi bi-check-circle me-1"></i>On Time
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="p-3 rounded text-center text-muted" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                        <i class="bi bi-info-circle me-2"></i>Please check in first to enable check out
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="col-lg-4">
                    <?php if ($today_attendance && $today_attendance['check_in_time']): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h5 class="card-title mb-4">
                                    <i class="bi bi-graph-up me-2"></i>Today's Summary
                                </h5>
                                
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Work Duration</div>
                                    <div class="h4 mb-0">
                                        <?php if ($today_attendance['work_minutes'] > 0): ?>
                                            <?php echo floor($today_attendance['work_minutes'] / 60); ?>h <?php echo $today_attendance['work_minutes'] % 60; ?>m
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Status</div>
                                    <div>
                                        <span class="badge bg-<?php 
                                            echo $today_attendance['status'] === 'present' ? 'success' : 
                                                ($today_attendance['status'] === 'late' ? 'warning' : 
                                                ($today_attendance['status'] === 'absent' ? 'danger' : 'secondary')); 
                                        ?> fs-6 px-3 py-2">
                                            <i class="bi bi-<?php 
                                                echo $today_attendance['status'] === 'present' ? 'check-circle' : 
                                                    ($today_attendance['status'] === 'late' ? 'exclamation-triangle' : 
                                                    ($today_attendance['status'] === 'absent' ? 'x-circle' : 'dash-circle')); 
                                            ?> me-1"></i>
                                            <?php echo ucfirst($today_attendance['status']); ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if ($today_attendance['check_in_time'] && $today_attendance['check_out_time']): ?>
                                    <hr class="my-3" style="border-color: var(--border-color);">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="text-muted small mb-1">Check In</div>
                                            <div class="fw-bold"><?php echo date('h:i A', strtotime($today_attendance['check_in_time'])); ?></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small mb-1">Check Out</div>
                                            <div class="fw-bold"><?php echo date('h:i A', strtotime($today_attendance['check_out_time'])); ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card shadow-sm">
                            <div class="card-body p-4 text-center">
                                <div class="mb-3">
                                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                </div>
                                <h6 class="text-muted">No attendance record for today</h6>
                                <p class="text-muted small mb-0">Check in to start tracking your attendance</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Stats -->
                    <?php if ($shift_info): ?>
                        <div class="card shadow-sm">
                            <div class="card-body p-4">
                                <h6 class="card-title mb-3">
                                    <i class="bi bi-info-circle me-2"></i>Shift Information
                                </h6>
                                <div class="mb-2">
                                    <div class="text-muted small">Shift Name</div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($shift_info['name']); ?></div>
                                </div>
                                <div class="mb-2">
                                    <div class="text-muted small">Grace Period</div>
                                    <div class="fw-bold"><?php echo $shift_info['grace_minutes']; ?> minutes</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

