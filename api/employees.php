<?php
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if user is admin or manager
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

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
                echo json_encode(['success' => false, 'message' => 'Password is required for new employees.']);
                $conn->close();
                exit();
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (role, department_id, shift_id, employee_code, name, email, password, phone, join_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siisssssss", $role, $department_id, $shift_id, $employee_code, $name, $email, $password_hash, $phone, $join_date, $status);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Employee created successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error creating employee: ' . $conn->error]);
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
                echo json_encode(['success' => true, 'message' => 'Employee updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating employee: ' . $conn->error]);
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role IN ('employee', 'manager')");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Employee deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting employee: ' . $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

