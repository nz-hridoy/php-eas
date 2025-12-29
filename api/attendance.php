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

// Check if user is employee
if ($_SESSION['role'] !== 'employee') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

// Handle check-in/check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $action = $_POST['action'] ?? '';
    
    // Get user's shift
    $userStmt = $conn->prepare("SELECT shift_id, department_id FROM users WHERE id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();
    
    if (!$user || !$user['shift_id']) {
        echo json_encode(['success' => false, 'message' => 'Your account is not properly configured. Please contact administrator.']);
        $conn->close();
        exit();
    }
    
    // Check if attendance record exists for today
    $checkStmt = $conn->prepare("SELECT * FROM attendance_records WHERE user_id = ? AND att_date = ?");
    $checkStmt->bind_param("is", $user_id, $today);
    $checkStmt->execute();
    $attendance = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();
    
    $current_time = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if ($action === 'check_in') {
        if ($attendance && $attendance['check_in_time']) {
            echo json_encode(['success' => false, 'message' => 'You have already checked in today.', 'type' => 'warning']);
        } else {
            // Get shift details for late calculation
            $shiftStmt = $conn->prepare("SELECT start_time, grace_minutes FROM shifts WHERE id = ?");
            $shiftStmt->bind_param("i", $user['shift_id']);
            $shiftStmt->execute();
            $shift = $shiftStmt->get_result()->fetch_assoc();
            $shiftStmt->close();
            
            $shift_start = strtotime($today . ' ' . $shift['start_time']);
            $check_in_time = strtotime($current_time);
            $late_minutes = max(0, floor(($check_in_time - $shift_start) / 60) - $shift['grace_minutes']);
            
            if ($attendance) {
                // Update existing record
                $updateStmt = $conn->prepare("UPDATE attendance_records SET check_in_time = ?, check_in_ip = ?, late_minutes = ?, status = ? WHERE id = ?");
                $status = $late_minutes > 0 ? 'late' : 'present';
                $updateStmt->bind_param("ssisi", $current_time, $ip_address, $late_minutes, $status, $attendance['id']);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Create new record
                $insertStmt = $conn->prepare("INSERT INTO attendance_records (user_id, att_date, shift_id, check_in_time, check_in_ip, late_minutes, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $status = $late_minutes > 0 ? 'late' : 'present';
                $insertStmt->bind_param("isissis", $user_id, $today, $user['shift_id'], $current_time, $ip_address, $late_minutes, $status);
                $insertStmt->execute();
                $insertStmt->close();
            }
            
            $message = 'Check-in successful!';
            if ($late_minutes > 0) {
                $message .= ' You are ' . $late_minutes . ' minutes late.';
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message, 
                'type' => 'success',
                'check_in_time' => $current_time,
                'late_minutes' => $late_minutes,
                'status' => $status
            ]);
        }
    } elseif ($action === 'check_out') {
        if (!$attendance || !$attendance['check_in_time']) {
            echo json_encode(['success' => false, 'message' => 'Please check in first.', 'type' => 'warning']);
        } elseif ($attendance['check_out_time']) {
            echo json_encode(['success' => false, 'message' => 'You have already checked out today.', 'type' => 'warning']);
        } else {
            // Get shift details for early calculation
            $shiftStmt = $conn->prepare("SELECT end_time FROM shifts WHERE id = ?");
            $shiftStmt->bind_param("i", $user['shift_id']);
            $shiftStmt->execute();
            $shift = $shiftStmt->get_result()->fetch_assoc();
            $shiftStmt->close();
            
            $shift_end = strtotime($today . ' ' . $shift['end_time']);
            $check_out_time = strtotime($current_time);
            $early_minutes = max(0, floor(($shift_end - $check_out_time) / 60));
            
            // Calculate work minutes
            $check_in = strtotime($attendance['check_in_time']);
            $work_minutes = max(0, floor(($check_out_time - $check_in) / 60));
            
            $updateStmt = $conn->prepare("UPDATE attendance_records SET check_out_time = ?, check_out_ip = ?, early_minutes = ?, work_minutes = ? WHERE id = ?");
            $updateStmt->bind_param("ssiii", $current_time, $ip_address, $early_minutes, $work_minutes, $attendance['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            $message = 'Check-out successful!';
            if ($early_minutes > 0) {
                $message .= ' You left ' . $early_minutes . ' minutes early.';
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message, 
                'type' => 'success',
                'check_out_time' => $current_time,
                'early_minutes' => $early_minutes,
                'work_minutes' => $work_minutes
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

