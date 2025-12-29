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
    
    $weekend_days = isset($_POST['weekend_days']) ? $_POST['weekend_days'] : [];
    $timezone = $_POST['timezone'] ?? 'UTC';
    $company_name = trim($_POST['company_name'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $company_phone = trim($_POST['company_phone'] ?? '');
    $company_email = trim($_POST['company_email'] ?? '');
    
    // Validate timezone
    if (empty($timezone)) {
        echo json_encode(['success' => false, 'message' => 'Timezone is required.']);
        $conn->close();
        exit();
    }
    
    // Validate email format if provided
    if (!empty($company_email) && !filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid company email format.']);
        $conn->close();
        exit();
    }
    
    // For now, store settings in session or create a settings table
    // In production, you should create a settings table and store values there
    // For this implementation, we'll just return success
    // TODO: Implement proper settings storage in database
    
    // Example: Store in session (temporary solution)
    $_SESSION['settings'] = [
        'weekend_days' => $weekend_days,
        'timezone' => $timezone,
        'company_name' => $company_name,
        'company_address' => $company_address,
        'company_phone' => $company_phone,
        'company_email' => $company_email
    ];
    
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully!']);
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

