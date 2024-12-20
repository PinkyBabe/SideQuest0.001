<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    checkUserRole(['admin']);

    // Validate required fields
    $required = ['firstName', 'lastName', 'email', 'password', 'officeName'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
    }

    // Validate password match
    if ($_POST['password'] !== $_POST['confirmPassword']) {
        throw new Exception('Passwords do not match');
    }

    $conn = Database::getInstance();

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $_POST['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Insert new faculty
    $stmt = $conn->prepare("
        INSERT INTO users (
            first_name, 
            last_name, 
            email, 
            actual_password, 
            role, 
            room_number,
            office_name,
            is_active
        ) VALUES (?, ?, ?, ?, 'faculty', ?, ?, 1)
    ");

    $stmt->bind_param(
        "ssssss",
        $_POST['firstName'],
        $_POST['lastName'],
        $_POST['email'],
        $_POST['password'],
        $_POST['roomNumber'],
        $_POST['officeName']
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Faculty added successfully'
        ]);
    } else {
        throw new Exception('Error adding faculty');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 