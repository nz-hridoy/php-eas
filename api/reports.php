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

// Handle GET requests for fetching report data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = getDBConnection();
    $report_type = $_GET['type'] ?? 'daily';
    
    // Initialize response data
    $response = [
        'success' => true,
        'type' => $report_type,
        'data' => [],
        'statistics' => []
    ];
    
    if ($report_type === 'daily') {
        $selected_date = $_GET['date'] ?? date('Y-m-d');
        
        // Get daily attendance
        $stmt = $conn->prepare("
            SELECT ar.*, u.name as employee_name, u.employee_code, d.name as department_name, s.name as shift_name
            FROM attendance_records ar
            INNER JOIN users u ON ar.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN shifts s ON ar.shift_id = s.id
            WHERE ar.att_date = ?
            ORDER BY u.name
        ");
        $stmt->bind_param("s", $selected_date);
        $stmt->execute();
        $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Calculate statistics
        $total_employees = count($records);
        $present_count = 0;
        $late_count = 0;
        $absent_count = 0;
        
        foreach ($records as $record) {
            if ($record['status'] === 'present') {
                $present_count++;
            } elseif ($record['status'] === 'late') {
                $present_count++;
                $late_count++;
            } elseif ($record['status'] === 'absent') {
                $absent_count++;
            }
        }
        
        $response['data'] = $records;
        $response['statistics'] = [
            'total_employees' => $total_employees,
            'present_count' => $present_count,
            'late_count' => $late_count,
            'absent_count' => $absent_count
        ];
        $response['date'] = $selected_date;
        
    } elseif ($report_type === 'monthly') {
        $selected_month = $_GET['month'] ?? date('Y-m');
        $month_start = $selected_month . '-01';
        $month_end = date('Y-m-t', strtotime($month_start));
        
        // Get monthly attendance summary
        $stmt = $conn->prepare("
            SELECT 
                u.id,
                u.name as employee_name,
                u.employee_code,
                d.name as department_name,
                COUNT(ar.id) as total_days,
                SUM(CASE WHEN ar.status IN ('present', 'late') THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(ar.work_minutes) as total_work_minutes
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN attendance_records ar ON u.id = ar.user_id AND ar.att_date >= ? AND ar.att_date <= ?
            WHERE u.role IN ('employee', 'manager')
            GROUP BY u.id, u.name, u.employee_code, d.name
            ORDER BY u.name
        ");
        $stmt->bind_param("ss", $month_start, $month_end);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Calculate overall statistics
        $total_employees = count($summary);
        $total_present = 0;
        $total_late = 0;
        $total_absent = 0;
        
        foreach ($summary as $row) {
            $total_present += $row['present_days'];
            $total_late += $row['late_days'];
            $total_absent += $row['absent_days'];
        }
        
        $response['data'] = $summary;
        $response['statistics'] = [
            'total_employees' => $total_employees,
            'total_present' => $total_present,
            'total_late' => $total_late,
            'total_absent' => $total_absent
        ];
        $response['month'] = $selected_month;
        
    } elseif ($report_type === 'late-early') {
        $selected_month = $_GET['month'] ?? date('Y-m');
        $month_start = $selected_month . '-01';
        $month_end = date('Y-m-t', strtotime($month_start));
        
        // Get late arrivals
        $lateStmt = $conn->prepare("
            SELECT 
                ar.*,
                u.name as employee_name,
                u.employee_code,
                d.name as department_name,
                s.name as shift_name,
                s.start_time as shift_start
            FROM attendance_records ar
            INNER JOIN users u ON ar.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN shifts s ON ar.shift_id = s.id
            WHERE ar.att_date >= ? AND ar.att_date <= ? AND ar.late_minutes > 0
            ORDER BY ar.late_minutes DESC, ar.att_date DESC
        ");
        $lateStmt->bind_param("ss", $month_start, $month_end);
        $lateStmt->execute();
        $late_records = $lateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $lateStmt->close();
        
        // Get early departures
        $earlyStmt = $conn->prepare("
            SELECT 
                ar.*,
                u.name as employee_name,
                u.employee_code,
                d.name as department_name,
                s.name as shift_name,
                s.end_time as shift_end
            FROM attendance_records ar
            INNER JOIN users u ON ar.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN shifts s ON ar.shift_id = s.id
            WHERE ar.att_date >= ? AND ar.att_date <= ? AND ar.early_minutes > 0
            ORDER BY ar.early_minutes DESC, ar.att_date DESC
        ");
        $earlyStmt->bind_param("ss", $month_start, $month_end);
        $earlyStmt->execute();
        $early_records = $earlyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $earlyStmt->close();
        
        $response['data'] = [
            'late_records' => $late_records,
            'early_records' => $early_records
        ];
        $response['statistics'] = [
            'late_count' => count($late_records),
            'early_count' => count($early_records)
        ];
        $response['month'] = $selected_month;
    }
    
    $conn->close();
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

