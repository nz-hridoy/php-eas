<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$conn = getDBConnection();

// Initialize variables
$records = [];
$summary = [];
$late_records = [];
$early_records = [];
$total_employees = 0;
$present_count = 0;
$late_count = 0;
$absent_count = 0;
$total_present = 0;
$total_late = 0;
$total_absent = 0;

if ($report_type === 'daily') {
    $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    // Get daily attendance
    $stmt = $conn->prepare("
        SELECT ar.*, u.name as employee_name, u.employee_code, d.name as department_name, s.name as shift_name
        FROM attendance_records ar
        INNER JOIN users u ON ar.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN shifts s ON ar.shift_id = s.id
        WHERE ar.att_date = ?
        ORDER BY u.name
    ");
    $stmt->bind_param("s", $selected_date);
    $stmt->execute();
    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Calculate statistics
    $total_employees = count($records);
    foreach ($records as $record) {
        if ($record['status'] === 'present') {
            $present_count++;
        } elseif ($record['status'] === 'late') {
            $present_count++;
            $late_count++;
        } elseif ($record['status'] === 'absent') {
            $absent_count++;
        }
    }
    
} elseif ($report_type === 'monthly') {
    $selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
    $month_start = $selected_month . '-01';
    $month_end = date('Y-m-t', strtotime($month_start));
    
    // Get monthly attendance summary
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name as employee_name,
            u.employee_code,
            d.name as department_name,
            COUNT(ar.id) as total_days,
            SUM(CASE WHEN ar.status IN ('present', 'late') THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(ar.work_minutes) as total_work_minutes
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN attendance_records ar ON u.id = ar.user_id AND ar.att_date >= ? AND ar.att_date <= ?
        WHERE u.role IN ('employee', 'manager')
        GROUP BY u.id, u.name, u.employee_code, d.name
        ORDER BY u.name
    ");
    $stmt->bind_param("ss", $month_start, $month_end);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Calculate overall statistics
    $total_employees = count($summary);
    foreach ($summary as $row) {
        $total_present += $row['present_days'];
        $total_late += $row['late_days'];
        $total_absent += $row['absent_days'];
    }
    
} elseif ($report_type === 'late-early') {
    $selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
    $month_start = $selected_month . '-01';
    $month_end = date('Y-m-t', strtotime($month_start));
    
    // Get late arrivals
    $lateStmt = $conn->prepare("
        SELECT 
            ar.*,
            u.name as employee_name,
            u.employee_code,
            d.name as department_name,
            s.name as shift_name,
            s.start_time as shift_start
        FROM attendance_records ar
        INNER JOIN users u ON ar.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN shifts s ON ar.shift_id = s.id
        WHERE ar.att_date >= ? AND ar.att_date <= ? AND ar.late_minutes > 0
        ORDER BY ar.late_minutes DESC, ar.att_date DESC
    ");
    $lateStmt->bind_param("ss", $month_start, $month_end);
    $lateStmt->execute();
    $late_records = $lateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $lateStmt->close();
    
    // Get early departures
    $earlyStmt = $conn->prepare("
        SELECT 
            ar.*,
            u.name as employee_name,
            u.employee_code,
            d.name as department_name,
            s.name as shift_name,
            s.end_time as shift_end
        FROM attendance_records ar
        INNER JOIN users u ON ar.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN shifts s ON ar.shift_id = s.id
        WHERE ar.att_date >= ? AND ar.att_date <= ? AND ar.early_minutes > 0
        ORDER BY ar.early_minutes DESC, ar.att_date DESC
    ");
    $earlyStmt->bind_param("ss", $month_start, $month_end);
    $earlyStmt->execute();
    $early_records = $earlyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $earlyStmt->close();
}

$conn->close();

?>
<?php 
$page_title = 'Reports';
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
                            <h1 class="h3 mb-0">Attendance Reports</h1>
                            <p class="text-muted">View and analyze attendance data</p>
                        </div>
                        <!-- Date/Month Filter -->
                        <?php if ($report_type === 'daily'): ?>
                            <form method="GET" class="d-inline">
                                <input type="hidden" name="type" value="daily">
                                <input type="date" name="date" class="form-control" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d'); ?>" onchange="this.form.submit()">
                            </form>
                        <?php else: ?>
                            <form method="GET" class="d-inline">
                                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                                <input type="month" name="month" class="form-control" value="<?php echo isset($_GET['month']) ? htmlspecialchars($_GET['month']) : date('Y-m'); ?>" onchange="this.form.submit()">
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Daily Report -->
            <?php if ($report_type === 'daily'): ?>
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Total</h5>
                                <h2 class="mb-0"><?php echo $total_employees; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success">Present</h5>
                                <h2 class="mb-0 text-success"><?php echo $present_count; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning">Late</h5>
                                <h2 class="mb-0 text-warning"><?php echo $late_count; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-danger">Absent</h5>
                                <h2 class="mb-0 text-danger"><?php echo $absent_count; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee Code</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Shift</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Work Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($records)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No attendance records found for this date.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($records as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['employee_code'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
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

            <!-- Monthly Report -->
            <?php elseif ($report_type === 'monthly'): ?>
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Total Employees</h5>
                                <h2 class="mb-0"><?php echo $total_employees; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success">Total Present</h5>
                                <h2 class="mb-0 text-success"><?php echo $total_present; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning">Total Late</h5>
                                <h2 class="mb-0 text-warning"><?php echo $total_late; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-danger">Total Absent</h5>
                                <h2 class="mb-0 text-danger"><?php echo $total_absent; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee Code</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Total Days</th>
                                        <th>Present</th>
                                        <th>Late</th>
                                        <th>Absent</th>
                                        <th>Total Work Hours</th>
                                        <th>Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($summary)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No attendance records found for this month.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($summary as $row): ?>
                                            <?php 
                                            $attendance_percent = $row['total_days'] > 0 ? round(($row['present_days'] / $row['total_days']) * 100, 1) : 0;
                                            $total_hours = floor($row['total_work_minutes'] / 60);
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['employee_code'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo $row['total_days']; ?></td>
                                                <td><span class="badge bg-success"><?php echo $row['present_days']; ?></span></td>
                                                <td><span class="badge bg-warning"><?php echo $row['late_days']; ?></span></td>
                                                <td><span class="badge bg-danger"><?php echo $row['absent_days']; ?></span></td>
                                                <td><?php echo $total_hours; ?>h</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $attendance_percent >= 90 ? 'success' : ($attendance_percent >= 70 ? 'warning' : 'danger'); ?>">
                                                        <?php echo $attendance_percent; ?>%
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

            <!-- Late & Early Report -->
            <?php elseif ($report_type === 'late-early'): ?>
                <!-- Late Arrivals -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Late Arrivals (<?php echo count($late_records); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Employee Code</th>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Shift</th>
                                                <th>Expected Time</th>
                                                <th>Check In Time</th>
                                                <th>Late By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($late_records)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted">No late arrivals found for this month.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($late_records as $record): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y', strtotime($record['att_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($record['employee_code'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($record['department_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($record['shift_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo date('h:i A', strtotime($record['att_date'] . ' ' . $record['shift_start'])); ?></td>
                                                        <td><?php echo date('h:i A', strtotime($record['check_in_time'])); ?></td>
                                                        <td><span class="badge bg-warning"><?php echo $record['late_minutes']; ?> minutes</span></td>
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

                <!-- Early Departures -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-clock"></i> Early Departures (<?php echo count($early_records); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Employee Code</th>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Shift</th>
                                                <th>Expected Time</th>
                                                <th>Check Out Time</th>
                                                <th>Early By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($early_records)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted">No early departures found for this month.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($early_records as $record): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y', strtotime($record['att_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($record['employee_code'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($record['department_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($record['shift_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo date('h:i A', strtotime($record['att_date'] . ' ' . $record['shift_end'])); ?></td>
                                                        <td><?php echo date('h:i A', strtotime($record['check_out_time'])); ?></td>
                                                        <td><span class="badge bg-warning"><?php echo $record['early_minutes']; ?> minutes</span></td>
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
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

