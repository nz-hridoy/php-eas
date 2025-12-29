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
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Department name is required.']);
            $conn->close();
            exit();
        }
        $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Department created successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating department: ' . $conn->error]);
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Department name is required.']);
            $conn->close();
            exit();
        }
        $stmt = $conn->prepare("UPDATE departments SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Department updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating department: ' . $conn->error]);
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Department deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting department: ' . $conn->error]);
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

