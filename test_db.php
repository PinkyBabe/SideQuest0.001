<?php
require_once 'includes/config.php';

try {
    // Test database connection
    $conn = Database::getInstance();
    echo "Database connection successful!<br>";

    // Test courses table
    $result = $conn->query("SELECT * FROM courses");
    if ($result) {
        echo "Courses table exists and has " . $result->num_rows . " rows<br>";
        while ($row = $result->fetch_assoc()) {
            echo "Course: " . $row['code'] . " - " . $row['name'];
            if ($row['major']) {
                echo " (Major: " . $row['major'] . ")";
            }
            echo "<br>";
        }
    } else {
        echo "Error accessing courses table: " . $conn->error . "<br>";
    }

    // Test users table structure
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        echo "<br>Users table structure:<br>";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "<br>";
        }
    } else {
        echo "Error accessing users table: " . $conn->error . "<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 