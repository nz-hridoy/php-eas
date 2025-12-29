<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

$today = date('Y-m-d');
$conn = getDBConnection();

// Get total counts
$total_employees = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('employee', 'manager')")->fetch_assoc()['count'];
$total_departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$total_shifts = $conn->query("SELECT COUNT(*) as count FROM shifts")->fetch_assoc()['count'];
$total_holidays = $conn->query("SELECT COUNT(*) as count FROM holidays WHERE holiday_date >= CURDATE()")->fetch_assoc()['count'];

// Get today's attendance statistics
$todayStmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
    FROM attendance_records 
    WHERE att_date = ?
");
$todayStmt->bind_param("s", $today);
$todayStmt->execute();
$today_stats = $todayStmt->get_result()->fetch_assoc();
$todayStmt->close();

// Get recent attendance records (last 10)
$recentStmt = $conn->prepare("
    SELECT ar.*, u.name as employee_name, u.employee_code, d.name as department_name, s.name as shift_name
    FROM attendance_records ar
    INNER JOIN users u ON ar.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN shifts s ON ar.shift_id = s.id
    WHERE ar.att_date <= ?
    ORDER BY ar.att_date DESC, ar.check_in_time DESC
    LIMIT 10
");
$recentStmt->bind_param("s", $today);
$recentStmt->execute();
$recent_records = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentStmt->close();

// Get monthly statistics
$current_month = date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

$monthlyStmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT ar.user_id) as active_employees,
        COUNT(ar.id) as total_records,
        SUM(CASE WHEN ar.status IN ('present', 'late') THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendance_records ar
    WHERE ar.att_date >= ? AND ar.att_date <= ?
");
$monthlyStmt->bind_param("ss", $month_start, $month_end);
$monthlyStmt->execute();
$monthly_stats = $monthlyStmt->get_result()->fetch_assoc();
$monthlyStmt->close();

$conn->close();

?>
<?php 
$page_title = 'Admin Dashboard';
include 'includes/head.php'; 
?>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="main-content" style="margin-bottom: 50px;">
        <div class="container-fluid">
            <!-- Dashboard Header -->
            <div class="dashboard-welcome-section mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h1 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>!</h1>
                        <p class="dashboard-subtitle">Here's what's happening with your attendance system today.</p>
                    </div>
                    <div class="dashboard-date">
                        <i class="bi bi-calendar3"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Overview Statistics -->
            <div class="row mb-4 g-3">
                <div class="col-md-3 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-people fs-1 text-primary"></i>
                            </div>
                            <h5 class="text-muted mb-1 small">Total Employees</h5>
                            <h2 class="mb-0"><?php echo $total_employees; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-building fs-1 text-info"></i>
                            </div>
                            <h5 class="text-muted mb-1 small">Departments</h5>
                            <h2 class="mb-0"><?php echo $total_departments; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-clock fs-1 text-warning"></i>
                            </div>
                            <h5 class="text-muted mb-1 small">Shifts</h5>
                            <h2 class="mb-0"><?php echo $total_shifts; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-calendar-event fs-1 text-success"></i>
                            </div>
                            <h5 class="text-muted mb-1 small">Upcoming Holidays</h5>
                            <h2 class="mb-0"><?php echo $total_holidays; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Attendance Statistics -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="bi bi-calendar-check me-2"></i>Today's Attendance Overview
                                </h5>
                                <a href="reports.php?type=daily&date=<?php echo $today; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-arrow-right me-1"></i>View Report
                                </a>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-3 d-flex">
                                    <div class="p-3 rounded w-100" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                        <div class="text-center">
                                            <div class="mb-2">
                                                <i class="bi bi-people fs-1 text-muted"></i>
                                            </div>
                                            <div class="text-muted small">Total Records</div>
                                            <div class="h4 mb-0"><?php echo $today_stats['total'] ?? 0; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex">
                                    <div class="p-3 rounded w-100" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                        <div class="text-center">
                                            <div class="mb-2">
                                                <i class="bi bi-check-circle fs-1 text-success"></i>
                                            </div>
                                            <div class="text-success small">Present</div>
                                            <div class="h4 mb-0 text-success"><?php echo $today_stats['present'] ?? 0; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex">
                                    <div class="p-3 rounded w-100" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                        <div class="text-center">
                                            <div class="mb-2">
                                                <i class="bi bi-clock-history fs-1 text-warning"></i>
                                            </div>
                                            <div class="text-warning small">Late</div>
                                            <div class="h4 mb-0 text-warning"><?php echo $today_stats['late'] ?? 0; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex">
                                    <div class="p-3 rounded w-100" style="background-color: var(--bg-primary); border: 1px solid var(--border-color);">
                                        <div class="text-center">
                                            <div class="mb-2">
                                                <i class="bi bi-x-circle fs-1 text-danger"></i>
                                            </div>
                                            <div class="text-danger small">Absent</div>
                                            <div class="h4 mb-0 text-danger"><?php echo $today_stats['absent'] ?? 0; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Statistics -->
            <div class="row mb-4 g-3">
                <div class="col-md-4 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-person-check fs-1 text-info"></i>
                            </div>
                            <h5 class="text-muted mb-1 small">Active Employees (This Month)</h5>
                            <h2 class="mb-0 text-info"><?php echo $monthly_stats['active_employees'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-calendar-check fs-1 text-success"></i>
                            </div>
                            <h5 class="text-muted mb-1 small">Total Present Days</h5>
                            <h2 class="mb-0 text-success"><?php echo $monthly_stats['present_days'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex">
                    <div class="card shadow-sm w-100">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-file-earmark-text fs-1 text-primary"></i>
                            </div>
                            <h5 class="text-muted mb-1 small">Total Records</h5>
                            <h2 class="mb-0"><?php echo $monthly_stats['total_records'] ?? 0; ?></h2>
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
                                    <i class="bi bi-clock-history me-2"></i>Recent Attendance Records
                                </h5>
                                <a href="reports.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-arrow-right me-1"></i>View All Reports
                                </a>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Shift</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
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
                                                    <td>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($record['employee_name']); ?></div>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($record['employee_code'] ?? 'N/A'); ?></div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($record['department_name'] ?? 'N/A'); ?></td>
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
                                                        <span class="badge bg-<?php 
                                                            echo $record['status'] === 'present' ? 'success' : 
                                                                ($record['status'] === 'late' ? 'warning' : 
                                                                ($record['status'] === 'absent' ? 'danger' : 'secondary')); 
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

