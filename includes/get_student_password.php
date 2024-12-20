<?php
// Turn off error display
ini_set('display_errors', 0);
error_reporting(0);

require_once 'session.php';
require_once 'config.php';
require_once 'functions.php';

// Check if user is admin
checkUserRole(['admin']);

// Clear any previous output
if (ob_get_length()) ob_clean();

$response = [
    'success' => false,
    'message' => '',
    'student_name' => '',
    'email' => '',
    'password' => ''
];

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Student ID is required');
    }

    $conn = Database::getInstance();
    
    $stmt = $conn->prepare("
        SELECT first_name, last_name, email, actual_password 
        FROM users 
        WHERE id = ? AND role = 'student'
    ");
    
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Student not found');
    }
    
    $student = $result->fetch_assoc();
    
    $response['success'] = true;
    $response['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
    $response['email'] = $student['email'];
    $response['password'] = $student['actual_password'];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in get_student_password: " . $e->getMessage());
}

// Ensure clean output
header('Content-Type: application/json');
echo json_encode($response);
exit; 