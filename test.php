<?php
require_once 'includes/config.php';

try {
    $conn = Database::getInstance();
    echo "Connected successfully to database!<br>";
    
    // Test courses table
    $result = $conn->query("SELECT * FROM courses");
    if ($result) {
        echo "Found " . $result->num_rows . " courses<br>";
        while ($row = $result->fetch_assoc()) {
            echo $row['code'] . " - " . $row['name'] . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    error_log("Test error: " . $e->getMessage());
}
?> 