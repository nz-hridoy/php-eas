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
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $grace_minutes = (int)($_POST['grace_minutes'] ?? 0);
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Shift name is required.']);
            $conn->close();
            exit();
        }
        
        if (empty($start_time)) {
            echo json_encode(['success' => false, 'message' => 'Start time is required.']);
            $conn->close();
            exit();
        }
        
        if (empty($end_time)) {
            echo json_encode(['success' => false, 'message' => 'End time is required.']);
            $conn->close();
            exit();
        }
        
        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO shifts (name, start_time, end_time, grace_minutes) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $start_time, $end_time, $grace_minutes);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Shift created successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creating shift: ' . $conn->error]);
            }
            $stmt->close();
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE shifts SET name = ?, start_time = ?, end_time = ?, grace_minutes = ? WHERE id = ?");
            $stmt->bind_param("sssii", $name, $start_time, $end_time, $grace_minutes, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Shift updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating shift: ' . $conn->error]);
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM shifts WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Shift deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting shift: ' . $conn->error]);
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

