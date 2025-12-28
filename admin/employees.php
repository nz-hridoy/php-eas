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
    
    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'employee';
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $shift_id = !empty($_POST['shift_id']) ? (int)$_POST['shift_id'] : null;
        $employee_code = trim($_POST['employee_code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $join_date = $_POST['join_date'] ?? null;
        $status = $_POST['status'] ?? 'active';
        
        if ($action === 'create') {
            if (empty($password)) {
                $message = 'Password is required for new employees.';
                $message_type = 'danger';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (role, department_id, shift_id, employee_code, name, email, password, phone, join_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siisssssss", $role, $department_id, $shift_id, $employee_code, $name, $email, $password_hash, $phone, $join_date, $status);
                if ($stmt->execute()) {
                    $message = 'Employee created successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error creating employee: ' . $conn->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        } else {
            $id = (int)$_POST['id'];
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET role = ?, department_id = ?, shift_id = ?, employee_code = ?, name = ?, email = ?, password = ?, phone = ?, join_date = ?, status = ? WHERE id = ?");
                $stmt->bind_param("siisssssssi", $role, $department_id, $shift_id, $employee_code, $name, $email, $password_hash, $phone, $join_date, $status, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET role = ?, department_id = ?, shift_id = ?, employee_code = ?, name = ?, email = ?, phone = ?, join_date = ?, status = ? WHERE id = ?");
                $stmt->bind_param("siissssssi", $role, $department_id, $shift_id, $employee_code, $name, $email, $phone, $join_date, $status, $id);
            }
            if ($stmt->execute()) {
                $message = 'Employee updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating employee: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role IN ('employee', 'manager')");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Employee deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting employee: ' . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
    $conn->close();
}

// Get all employees
$conn = getDBConnection();
$employees = $conn->query("
    SELECT u.*, d.name as department_name, s.name as shift_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    LEFT JOIN shifts s ON u.shift_id = s.id 
    WHERE u.role IN ('employee', 'manager')
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get departments and shifts for dropdowns
$departments = $conn->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$shifts = $conn->query("SELECT * FROM shifts ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$conn->close();

?>
<?php 
$page_title = 'Employees';
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
                            <h1 class="h3 mb-0">Employees</h1>
                            <p class="text-muted">Manage employees</p>
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal" onclick="resetForm()">
                            <i class="bi bi-plus-circle"></i> Add Employee
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
                                    <th>Employee Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Shift</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No employees found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($emp['employee_code'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['department_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($emp['shift_name'] ?? 'N/A'); ?></td>
                                            <td><span class="badge bg-info"><?php echo ucfirst($emp['role']); ?></span></td>
                                            <td><span class="badge bg-<?php echo $emp['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($emp['status']); ?></span></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($emp)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
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

    <!-- Employee Modal -->
    <div class="modal fade" id="employeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="employeeAction" value="create">
                        <input type="hidden" name="id" id="employeeId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee Code</label>
                                <input type="text" class="form-control" name="employee_code" id="employeeCode" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="name" id="employeeName" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="employeeEmail" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <small class="text-muted">(leave blank to keep current)</small></label>
                                <input type="password" class="form-control" name="password" id="employeePassword">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="employeePhone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Join Date</label>
                                <input type="date" class="form-control" name="join_date" id="employeeJoinDate">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" id="employeeRole" required>
                                    <option value="employee">Employee</option>
                                    <option value="manager">Manager</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department_id" id="employeeDepartment">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Shift</label>
                                <select class="form-select" name="shift_id" id="employeeShift">
                                    <option value="">Select Shift</option>
                                    <?php foreach ($shifts as $shift): ?>
                                        <option value="<?php echo $shift['id']; ?>"><?php echo htmlspecialchars($shift['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="employeeStatus" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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
            document.getElementById('employeeAction').value = 'create';
            document.getElementById('employeeId').value = '';
            document.getElementById('employeeModal').querySelector('.modal-title').textContent = 'Add Employee';
            document.getElementById('employeeModal').querySelector('form').reset();
        }
        
        function editEmployee(emp) {
            document.getElementById('employeeAction').value = 'update';
            document.getElementById('employeeId').value = emp.id;
            document.getElementById('employeeCode').value = emp.employee_code || '';
            document.getElementById('employeeName').value = emp.name || '';
            document.getElementById('employeeEmail').value = emp.email || '';
            document.getElementById('employeePhone').value = emp.phone || '';
            document.getElementById('employeeJoinDate').value = emp.join_date || '';
            document.getElementById('employeeRole').value = emp.role || 'employee';
            document.getElementById('employeeDepartment').value = emp.department_id || '';
            document.getElementById('employeeShift').value = emp.shift_id || '';
            document.getElementById('employeeStatus').value = emp.status || 'active';
            document.getElementById('employeePassword').required = false;
            document.getElementById('employeeModal').querySelector('.modal-title').textContent = 'Edit Employee';
            
            const modal = new bootstrap.Modal(document.getElementById('employeeModal'));
            modal.show();
        }
    </script>

    <?php include 'includes/footer.php'; ?>

