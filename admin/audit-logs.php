<?php
require_once '../config/database.php';

// Check if user is admin or manager
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../index.php');
    exit();
}

$conn = getDBConnection();

// Get filter parameters
$filter_action = $_GET['action'] ?? '';
$filter_entity = $_GET['entity'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($filter_action)) {
    $where_conditions[] = "al.action = ?";
    $params[] = $filter_action;
    $param_types .= 's';
}

if (!empty($filter_entity)) {
    $where_conditions[] = "al.entity = ?";
    $params[] = $filter_entity;
    $param_types .= 's';
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $filter_date_from;
    $param_types .= 's';
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $filter_date_to;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM audit_logs al $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_records / $per_page);

// Get audit logs
$query = "
    SELECT al.*, u.name as user_name, u.email as user_email
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    $where_clause
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($param_types)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$audit_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unique actions and entities for filters
$actions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetch_all(MYSQLI_ASSOC);
$entities = $conn->query("SELECT DISTINCT entity FROM audit_logs ORDER BY entity")->fetch_all(MYSQLI_ASSOC);

$conn->close();

?>
<?php 
$page_title = 'Audit Logs';
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
                            <h1 class="h3 mb-0">Audit Logs</h1>
                            <p class="text-muted">View system activity and changes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Action</label>
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $filter_action === $action['action'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($action['action'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Entity</label>
                            <select name="entity" class="form-select">
                                <option value="">All Entities</option>
                                <?php foreach ($entities as $entity): ?>
                                    <option value="<?php echo htmlspecialchars($entity['entity']); ?>" <?php echo $filter_entity === $entity['entity'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($entity['entity'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Audit Logs Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="text-muted">Total Records: <strong><?php echo $total_records; ?></strong></span>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                    <th>Entity ID</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($audit_logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No audit logs found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($audit_logs as $log): ?>
                                        <tr>
                                            <td>
                                                <div><?php echo date('M d, Y', strtotime($log['created_at'])); ?></div>
                                                <div class="text-muted small"><?php echo date('h:i A', strtotime($log['created_at'])); ?></div>
                                            </td>
                                            <td>
                                                <?php if ($log['user_name']): ?>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($log['user_name']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($log['user_email']); ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $log['action'] === 'create' ? 'success' : 
                                                        ($log['action'] === 'update' ? 'info' : 
                                                        ($log['action'] === 'delete' ? 'danger' : 'secondary')); 
                                                ?>">
                                                    <?php echo htmlspecialchars(ucfirst($log['action'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars(ucfirst($log['entity'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['entity_id']): ?>
                                                    <span class="text-muted">#<?php echo $log['entity_id']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($log['ip']): ?>
                                                    <code class="text-muted"><?php echo htmlspecialchars($log['ip']); ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                $query_params = $_GET;
                                
                                // Previous button
                                if ($page > 1):
                                    $query_params['page'] = $page - 1;
                                    $prev_url = '?' . http_build_query($query_params);
                                ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $prev_url; ?>">
                                            <i class="bi bi-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="bi bi-chevron-left"></i> Previous
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <?php
                                // Page numbers
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                    $query_params['page'] = $i;
                                    $page_url = '?' . http_build_query($query_params);
                                ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $page_url; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php
                                // Next button
                                if ($page < $total_pages):
                                    $query_params['page'] = $page + 1;
                                    $next_url = '?' . http_build_query($query_params);
                                ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $next_url; ?>">
                                            Next <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            Next <i class="bi bi-chevron-right"></i>
                                        </span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

