<?php
require_once 'includes/config.php';

function testLogin($email, $password) {
    try {
        $conn = Database::getInstance();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            echo "User not found with email: $email<br>";
            return;
        }
        
        echo "Found user:<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
        
        if ($user['role'] === 'admin') {
            $valid = ($password === $user['password']);
        } else {
            $valid = password_verify($password, $user['password']);
        }
        
        echo "Password validation: " . ($valid ? 'Success' : 'Failed') . "<br>";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}

// Test admin login
echo "<h2>Testing Admin Login</h2>";
testLogin('admin@example.com', 'admin123'); 