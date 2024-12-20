<?php
require_once 'includes/config.php';

try {
    $conn = Database::getInstance();
    
    // Add is_active and updated_at columns
    $sql = "ALTER TABLE users 
            ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1,
            ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    
    if ($conn->query($sql)) {
        // Set all existing users to active
        $conn->query("UPDATE users SET is_active = 1 WHERE is_active IS NULL");
        echo "Database updated successfully! Added is_active and updated_at columns.";
    } else {
        throw new Exception("Error updating database: " . $conn->error);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 