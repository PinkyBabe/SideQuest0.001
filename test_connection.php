<?php
require_once 'includes/config.php';

try {
    $conn = Database::getInstance();
    echo "Database connection successful!<br>";
    
    // Check tables
    $tables_result = $conn->query("SHOW TABLES");
    echo "<br>Database tables:<br>";
    while ($row = $tables_result->fetch_array()) {
        echo "- " . $row[0] . "<br>";
    }
    
    // Test courses table
    $result = $conn->query("SELECT COUNT(*) as count FROM courses");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<br>Found " . $row['count'] . " courses in database<br>";
        
        // Show course details
        $courses = $conn->query("SELECT * FROM courses");
        while ($course = $courses->fetch_assoc()) {
            echo "Course: " . $course['code'] . " - " . $course['name'];
            if ($course['major']) {
                echo " (Major: " . $course['major'] . ")";
            }
            echo "<br>";
        }
    } else {
        echo "<br>Error accessing courses table: " . $conn->error . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
} 