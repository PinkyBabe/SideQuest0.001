<?php
// Prevent any output before headers
ob_start();

// Set JSON content type
header('Content-Type: application/json');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_errors.log');

session_start();

require_once 'config.php';
require_once 'functions.php';

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $response = [
        'success' => false,
        'error' => null,
        'role' => null
    ];

    try {
        // Validate input
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || empty($password)) {
            throw new Exception('Please enter valid email and password');
        }

        $conn = Database::getInstance();
        
        // Get user from database
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        if (!$stmt) {
            error_log("Database prepare error: " . $conn->error);
            throw new Exception("System error, please try again");
        }

        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            error_log("Query execution error: " . $stmt->error);
            throw new Exception("System error, please try again");
        }

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            throw new Exception('Invalid email or password');
        }

        // Debug log
        error_log("Login attempt - User data: " . print_r($user, true));

        // Verify password based on role
        $valid = false;
        if ($user['role'] === 'admin') {
            // For admin, direct password comparison
            $valid = ($password === $user['actual_password']);
        } else {
            // For faculty and students, check actual_password
            $valid = ($password === $user['actual_password']);
        }

        if (!$valid) {
            throw new Exception('Invalid email or password');
        }

        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];

        error_log("Login successful - User ID: {$user['id']}, Role: {$user['role']}");

        $response['success'] = true;
        $response['role'] = $user['role'];

    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        error_log("Login error for {$email}: " . $e->getMessage());
        http_response_code(400);
    }

    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send JSON response
    echo json_encode($response);
    exit;
}

// If not a login request and user is not logged in, return error
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

// For all other requests, verify authentication
require_once 'auth_middleware.php';