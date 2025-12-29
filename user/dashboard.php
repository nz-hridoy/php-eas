<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Ensure only employees can access (admin and manager should not access user dashboard)
if ($_SESSION['role'] !== 'employee') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$current_month = date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

$conn = getDBConnection();

// Get today's attendance
$todayStmt = $conn->prepare("SELECT ar.*, s.name as shift_name, s.start_time, s.end_time FROM attendance_records ar LEFT JOIN shifts s ON ar.shift_id = s.id WHERE ar.user_id = ? AND ar.att_date = ?");
$todayStmt->bind_param("is", $user_id, $today);
$todayStmt->execute();
$today_attendance = $todayStmt->get_result()->fetch_assoc();
$todayStmt->close();

// Get monthly statistics
$monthlyStmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(work_minutes) as total_work_minutes
    FROM attendance_records 
    WHERE user_id = ? AND att_date >= ? AND att_date <= ?
");
$monthlyStmt->bind_param("iss", $user_id, $month_start, $month_end);
$monthlyStmt->execute();
$monthly_stats = $monthlyStmt->get_result()->fetch_assoc();
$monthlyStmt->close();

// Get recent attendance records (last 7 days)
$recentStmt = $conn->prepare("
    SELECT ar.*, s.name as shift_name 
    FROM attendance_records ar 
    LEFT JOIN shifts s ON ar.shift_id = s.id 
    WHERE ar.user_id = ? AND ar.att_date <= ? 
    ORDER BY ar.att_date DESC 
    LIMIT 7
");
$recentStmt->bind_param("is", $user_id, $today);
$recentStmt->execute();
$recent_records = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentStmt->close();

// Get user's shift info
$shiftStmt = $conn->prepare("SELECT s.* FROM shifts s INNER JOIN users u ON s.id = u.shift_id WHERE u.id = ?");
$shiftStmt->bind_param("i", $user_id);
$shiftStmt->execute();
$shift_info = $shiftStmt->get_result()->fetch_assoc();
$shiftStmt->close();

$conn->close();

// Calculate attendance percentage
$attendance_percent = 0;
if ($monthly_stats['total_days'] > 0) {
    $attendance_percent = round(($monthly_stats['present_days'] / $monthly_stats['total_days']) * 100, 1);
}

?>
<?php 
$page_title = 'User Dashboard';
include 'includes/head.php'; 
?>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Dashboard Header -->
            <div class="dashboard-welcome-section mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h1 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>!</h1>
                        <p class="dashboard-subtitle">Here's your attendance overview for today.</p>
                    </div>
                    <div class="dashboard-date">
                        <i class="bi bi-calendar3"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Today's Attendance Status -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="bi bi-calendar-check me-2"></i>Today's Attendance
                                </h5>
                                <?php if ($shift_info): ?>
                                    <span class="badge bg-info fs-6 px-3 py-2">
                                        <i class="bi bi-clock"></i> <?php echo htmlspecialchars($shift_info['name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($today_attendance && $today_attendance['check_in_time']): ?>
                                <div class="row g-3">
                                    <div class="col-md-4 d-flex">
                                        <div class="p-3 rounded w-100" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-check-lg text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="text-muted small">Check In</div>
                                                    <div class="fw-bold"><?php echo date('h:i A', strtotime($today_attendance['check_in_time'])); ?></div>
                                                    <?php if ($today_attendance['late_minutes'] > 0): ?>
                                                        <span class="badge bg-warning mt-1"><?php echo $today_attendance['late_minutes']; ?> min late</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex">
                                        <div class="p-3 rounded w-100" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle <?php echo $today_attendance['check_out_time'] ? 'bg-secondary' : 'bg-warning'; ?> d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-<?php echo $today_attendance['check_out_time'] ? 'check-lg' : 'clock'; ?> text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="text-muted small">Check Out</div>
                                                    <?php if ($today_attendance['check_out_time']): ?>
                                                        <div class="fw-bold"><?php echo date('h:i A', strtotime($today_attendance['check_out_time'])); ?></div>
                                                        <?php if ($today_attendance['early_minutes'] > 0): ?>
                                                            <span class="badge bg-warning mt-1"><?php echo $today_attendance['early_minutes']; ?> min early</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="fw-bold text-warning">Pending</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex">
                                        <div class="p-3 rounded w-100" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-info d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-clock-history text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="text-muted small">Work Duration</div>
                                                    <?php if ($today_attendance['work_minutes'] > 0): ?>
                                                        <div class="fw-bold"><?php echo floor($today_attendance['work_minutes'] / 60); ?>h <?php echo $today_attendance['work_minutes'] % 60; ?>m</div>
                                                    <?php else: ?>
                                                        <div class="fw-bold text-muted">-</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <a href="todays-attendance.php" class="btn btn-primary">
                                        <i class="bi bi-calendar-check me-2"></i>View Full Details
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <h6 class="text-muted mb-2">No attendance record for today</h6>
                                    <p class="text-muted small mb-3">Check in to start tracking your attendance</p>
                                    <a href="todays-attendance.php" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Check In Now
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Statistics -->
            <div class="row mb-4 g-3">
                <div class="col-md-3 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-calendar-check fs-1 text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-1 small">Total Days</h5>
                            <h2 class="mb-0"><?php echo $monthly_stats['total_days'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-check-circle fs-1 text-success"></i>
                            </div>
                            <h5 class="text-success mb-1 small">Present</h5>
                            <h2 class="mb-0 text-success"><?php echo $monthly_stats['present_days'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-clock-history fs-1 text-warning"></i>
                            </div>
                            <h5 class="text-warning mb-1 small">Late</h5>
                            <h2 class="mb-0 text-warning"><?php echo $monthly_stats['late_days'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-percent fs-1 text-info"></i>
                            </div>
                            <h5 class="text-info mb-1 small">Attendance %</h5>
                            <h2 class="mb-0 text-info"><?php echo $attendance_percent; ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance Records -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock-history me-2"></i>Recent Attendance
                                </h5>
                                <a href="my-attendance.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-arrow-right me-1"></i>View All
                                </a>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Shift</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Work Hours</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_records)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No attendance records found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_records as $record): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($record['att_date'])); ?></td>
                                                    <td><?php echo date('D', strtotime($record['att_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($record['shift_name'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php if ($record['check_in_time']): ?>
                                                            <?php echo date('h:i A', strtotime($record['check_in_time'])); ?>
                                                            <?php if ($record['late_minutes'] > 0): ?>
                                                                <span class="badge bg-warning ms-1"><?php echo $record['late_minutes']; ?>m</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($record['check_out_time']): ?>
                                                            <?php echo date('h:i A', strtotime($record['check_out_time'])); ?>
                                                            <?php if ($record['early_minutes'] > 0): ?>
                                                                <span class="badge bg-warning ms-1"><?php echo $record['early_minutes']; ?>m</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($record['work_minutes'] > 0): ?>
                                                            <?php echo floor($record['work_minutes'] / 60); ?>h <?php echo $record['work_minutes'] % 60; ?>m
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $record['status'] === 'present' ? 'success' : 
                                                                ($record['status'] === 'late' ? 'warning' : 
                                                                ($record['status'] === 'absent' ? 'danger' : 
                                                                ($record['status'] === 'holiday' ? 'info' : 'secondary'))); 
                                                        ?>">
                                                            <?php echo ucfirst($record['status']); ?>
                                                        </span>
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
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

