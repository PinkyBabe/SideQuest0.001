<?php
require_once 'session.php';
require_once 'config.php';
require_once 'functions.php';

// Check if user is admin
checkUserRole(['admin']);

$response = [
    'success' => false,
    'message' => '',
    'faculty_name' => '',
    'email' => '',
    'password' => ''
];

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Faculty ID is required');
    }

    $conn = Database::getInstance();
    
    // Get faculty details including the actual password
    $stmt = $conn->prepare("
        SELECT first_name, last_name, email, actual_password 
        FROM users 
        WHERE id = ? AND role = 'faculty'
    ");
    
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Faculty not found');
    }
    
    $faculty = $result->fetch_assoc();
    
    $response['success'] = true;
    $response['faculty_name'] = $faculty['first_name'] . ' ' . $faculty['last_name'];
    $response['email'] = $faculty['email'];
    $response['password'] = $faculty['actual_password'];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in get_faculty_password: " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
exit; 