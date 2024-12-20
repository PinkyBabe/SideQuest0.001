<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    $email = $data['email'];
    $password = $data['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }
    
    $conn = Database::getInstance();
    
    // Get user with this email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Invalid email or password');
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['actual_password'])) {
        throw new Exception('Invalid email or password');
    }
    
    // Check if account is active
    if (!$user['is_active']) {
        throw new Exception('Account is inactive');
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    error_log("User logged in - ID: {$user['id']}, Role: {$user['role']}, Name: {$user['first_name']} {$user['last_name']}");
    
    // Return success response with role for redirect
    echo json_encode([
        'success' => true,
        'role' => $user['role'],
        'message' => 'Login successful'
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 