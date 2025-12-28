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
        $holiday_date = $_POST['holiday_date'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, title) VALUES (?, ?)");
        $stmt->bind_param("ss", $holiday_date, $title);
        if ($stmt->execute()) {
            $message = 'Holiday created successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error creating holiday: ' . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $holiday_date = $_POST['holiday_date'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $stmt = $conn->prepare("UPDATE holidays SET holiday_date = ?, title = ? WHERE id = ?");
        $stmt->bind_param("ssi", $holiday_date, $title, $id);
        if ($stmt->execute()) {
            $message = 'Holiday updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating holiday: ' . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM holidays WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Holiday deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting holiday: ' . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
    $conn->close();
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
                                    <th>Date</th>
                                    <th>Title</th>
                                    <th>Day</th>
                                    <th>Actions</th>
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
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editHoliday(<?php echo htmlspecialchars(json_encode($holiday)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this holiday?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $holiday['id']; ?>">
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

    <!-- Holiday Modal -->
    <div class="modal fade" id="holidayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Holiday</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="holidayAction" value="create">
                        <input type="hidden" name="id" id="holidayId">
                        
                        <div class="mb-3">
                            <label class="form-label">Holiday Date</label>
                            <input type="date" class="form-control" name="holiday_date" id="holidayDate" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="holidayTitle" required>
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
            document.getElementById('holidayAction').value = 'create';
            document.getElementById('holidayId').value = '';
            document.getElementById('holidayModal').querySelector('.modal-title').textContent = 'Add Holiday';
            document.getElementById('holidayModal').querySelector('form').reset();
        }
        
        function editHoliday(holiday) {
            document.getElementById('holidayAction').value = 'update';
            document.getElementById('holidayId').value = holiday.id;
            document.getElementById('holidayDate').value = holiday.holiday_date || '';
            document.getElementById('holidayTitle').value = holiday.title || '';
            document.getElementById('holidayModal').querySelector('.modal-title').textContent = 'Edit Holiday';
            
            const modal = new bootstrap.Modal(document.getElementById('holidayModal'));
            modal.show();
        }
    </script>

    <?php include 'includes/footer.php'; ?>

