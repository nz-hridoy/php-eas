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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required.']);
        $conn->close();
        exit();
    }
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required.']);
        $conn->close();
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        $conn->close();
        exit();
    }
    
    // Check if email is already taken by another user
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->bind_param("si", $email, $user_id);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Email is already taken by another user.']);
        $conn->close();
        exit();
    }
    
    // Update profile
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $password_hash, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
    }
    
    if ($stmt->execute()) {
        // Update session name
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating profile: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

