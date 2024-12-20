<?php
require_once 'includes/config.php';

try {
    $conn = Database::getInstance();
    
    // Check if admin exists
    $result = $conn->query("SELECT * FROM users WHERE role = 'admin'");
    
    if ($result->num_rows === 0) {
        // Create admin if not exists
        $sql = "INSERT INTO users (
            first_name, 
            last_name, 
            email, 
            password, 
            actual_password,
            role,
            is_active
        ) VALUES (
            'Admin',
            'User',
            'admin@example.com',
            'admin123',
            'admin123',
            'admin',
            1
        )";
        
        if ($conn->query($sql)) {
            echo "Admin user created successfully!<br>";
            echo "Email: admin@example.com<br>";
            echo "Password: admin123<br>";
        } else {
            throw new Exception("Error creating admin: " . $conn->error);
        }
    } else {
        $admin = $result->fetch_assoc();
        echo "Admin user exists:<br>";
        echo "Email: " . $admin['email'] . "<br>";
        echo "Password: " . $admin['actual_password'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 