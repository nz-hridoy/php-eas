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
$current_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// Get attendance records for the month
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT ar.*, s.name as shift_name, s.start_time, s.end_time 
    FROM attendance_records ar 
    LEFT JOIN shifts s ON ar.shift_id = s.id 
    WHERE ar.user_id = ? AND ar.att_date >= ? AND ar.att_date <= ?
    ORDER BY ar.att_date DESC
");
$stmt->bind_param("iss", $user_id, $month_start, $month_end);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$total_days = 0;
$present_days = 0;
$late_days = 0;
$absent_days = 0;
$total_work_minutes = 0;

foreach ($records as $record) {
    $total_days++;
    if ($record['status'] === 'present' || $record['status'] === 'late') {
        $present_days++;
        if ($record['status'] === 'late') {
            $late_days++;
        }
    } elseif ($record['status'] === 'absent') {
        $absent_days++;
    }
    $total_work_minutes += $record['work_minutes'] ?? 0;
}

$conn->close();

?>
<?php 
$page_title = 'My Attendance';
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
                            <h1 class="h3 mb-0">My Attendance</h1>
                            <p class="text-muted">Monthly attendance records</p>
                        </div>
                        <form method="GET" class="d-flex gap-2">
                            <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($current_month); ?>" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Total Days</h5>
                            <h2 class="mb-0"><?php echo $total_days; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success">Present</h5>
                            <h2 class="mb-0 text-success"><?php echo $present_days; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning">Late</h5>
                            <h2 class="mb-0 text-warning"><?php echo $late_days; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-danger">Absent</h5>
                            <h2 class="mb-0 text-danger"><?php echo $absent_days; ?></h2>
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
                                <?php if (empty($records)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No attendance records found for this month.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($record['att_date'])); ?></td>
                                            <td><?php echo date('D', strtotime($record['att_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['shift_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($record['check_in_time']): ?>
                                                    <?php echo date('h:i A', strtotime($record['check_in_time'])); ?>
                                                    <?php if ($record['late_minutes'] > 0): ?>
                                                        <span class="badge bg-warning ms-1"><?php echo $record['late_minutes']; ?>m late</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['check_out_time']): ?>
                                                    <?php echo date('h:i A', strtotime($record['check_out_time'])); ?>
                                                    <?php if ($record['early_minutes'] > 0): ?>
                                                        <span class="badge bg-warning ms-1"><?php echo $record['early_minutes']; ?>m early</span>
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
    </main>

    <?php include 'includes/footer.php'; ?>

