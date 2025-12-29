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
        $holiday_date = $_POST['holiday_date'] ?? '';
        $title = trim($_POST['title'] ?? '');
        
        if (empty($holiday_date)) {
            echo json_encode(['success' => false, 'message' => 'Holiday date is required.']);
            $conn->close();
            exit();
        }
        
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Title is required.']);
            $conn->close();
            exit();
        }
        
        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, title) VALUES (?, ?)");
            $stmt->bind_param("ss", $holiday_date, $title);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Holiday created successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creating holiday: ' . $conn->error]);
            }
            $stmt->close();
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE holidays SET holiday_date = ?, title = ? WHERE id = ?");
            $stmt->bind_param("ssi", $holiday_date, $title, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Holiday updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating holiday: ' . $conn->error]);
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM holidays WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Holiday deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting holiday: ' . $conn->error]);
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

