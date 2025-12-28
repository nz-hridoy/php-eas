<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $grace_minutes = (int)($_POST['grace_minutes'] ?? 0);
        $stmt = $conn->prepare("INSERT INTO shifts (name, start_time, end_time, grace_minutes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $start_time, $end_time, $grace_minutes);
        if ($stmt->execute()) {
            $message = 'Shift created successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error creating shift: ' . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $grace_minutes = (int)($_POST['grace_minutes'] ?? 0);
        $stmt = $conn->prepare("UPDATE shifts SET name = ?, start_time = ?, end_time = ?, grace_minutes = ? WHERE id = ?");
        $stmt->bind_param("sssii", $name, $start_time, $end_time, $grace_minutes, $id);
        if ($stmt->execute()) {
            $message = 'Shift updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating shift: ' . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM shifts WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Shift deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting shift: ' . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
    $conn->close();
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

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

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
                                    <th>Actions</th>
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
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editShift(<?php echo htmlspecialchars(json_encode($shift)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this shift?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $shift['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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
    <div class="modal fade" id="shiftModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="shiftAction" value="create">
                        <input type="hidden" name="id" id="shiftId">
                        
                        <div class="mb-3">
                            <label class="form-label">Shift Name</label>
                            <input type="text" class="form-control" name="name" id="shiftName" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" class="form-control" name="start_time" id="shiftStartTime" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" class="form-control" name="end_time" id="shiftEndTime" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Grace Minutes</label>
                            <input type="number" class="form-control" name="grace_minutes" id="shiftGraceMinutes" value="0" min="0">
                            <small class="text-muted">Allowed late minutes before marking as late</small>
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
        function resetForm() {
            document.getElementById('shiftAction').value = 'create';
            document.getElementById('shiftId').value = '';
            document.getElementById('shiftModal').querySelector('.modal-title').textContent = 'Add Shift';
            document.getElementById('shiftModal').querySelector('form').reset();
        }
        
        function editShift(shift) {
            document.getElementById('shiftAction').value = 'update';
            document.getElementById('shiftId').value = shift.id;
            document.getElementById('shiftName').value = shift.name || '';
            document.getElementById('shiftStartTime').value = shift.start_time || '';
            document.getElementById('shiftEndTime').value = shift.end_time || '';
            document.getElementById('shiftGraceMinutes').value = shift.grace_minutes || 0;
            document.getElementById('shiftModal').querySelector('.modal-title').textContent = 'Edit Shift';
            
            const modal = new bootstrap.Modal(document.getElementById('shiftModal'));
            modal.show();
        }
    </script>

    <?php include 'includes/footer.php'; ?>

