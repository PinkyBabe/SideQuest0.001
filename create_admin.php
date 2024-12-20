<?php
require_once 'includes/config.php';

try {
    $conn = Database::getInstance();

    // Drop and recreate users table
    $conn->query("DROP TABLE IF EXISTS users");
    $conn->query("
        CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'faculty', 'student') NOT NULL,
            room_number VARCHAR(50),
            office_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create admin user
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    
    $first_name = 'Admin';
    $last_name = 'User';
    $email = 'admin@example.com';
    $password = 'admin123';
    $role = 'admin';

    $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $role);

    if ($stmt->execute()) {
        echo "<div style='text-align: center; padding: 20px; background-color: #dff0d8; color: #3c763d; margin: 20px;'>";
        echo "<h2>Admin user created successfully!</h2>";
        echo "<p>Email: admin@example.com</p>";
        echo "<p>Password: admin123</p>";
        echo "<p><a href='index.php'>Go to Login Page</a></p>";
        echo "</div>";
    } else {
        throw new Exception("Error creating admin user: " . $stmt->error);
    }
} catch (Exception $e) {
    echo "<div style='text-align: center; padding: 20px; background-color: #f2dede; color: #a94442; margin: 20px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 