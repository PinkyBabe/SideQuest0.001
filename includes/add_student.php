<?php
require_once 'session.php';
require_once 'config.php';
require_once 'functions.php';

// Check if user is admin
checkUserRole(['admin']);

$response = [
    'success' => false,
    'message' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required_fields = ['firstName', 'lastName', 'email', 'password', 'course'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception($field . ' is required');
        }
    }

    $conn = Database::getInstance();

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $_POST['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Hash password
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new student
    $stmt = $conn->prepare("
        INSERT INTO users (
            first_name,
            last_name,
            email,
            password,
            actual_password,
            role,
            course,
            is_active
        ) VALUES (?, ?, ?, ?, ?, 'student', ?, 1)
    ");

    $stmt->bind_param(
        "ssssss",
        $_POST['firstName'],
        $_POST['lastName'],
        $_POST['email'],
        $hashed_password,
        $password,
        $_POST['course']
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Student added successfully';
        $response['student'] = [
            'id' => $conn->insert_id,
            'first_name' => $_POST['firstName'],
            'last_name' => $_POST['lastName'],
            'email' => $_POST['email'],
            'course' => $_POST['course']
        ];
    } else {
        throw new Exception('Error adding student: ' . $stmt->error);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in add_student: " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response); 