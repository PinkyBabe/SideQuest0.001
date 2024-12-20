<?php
require_once 'config.php';
require_once 'auth_middleware.php';
require_once 'helpers.php';

// Prevent any output before our JSON response
ob_start();

try {
    checkUserRole(['student']);
    
    $conn = Database::getInstance();
    
    // Check if database connection is valid
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Check if quests table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'quests'");
    if ($tableCheck->num_rows === 0) {
        throw new Exception("Database tables not initialized");
    }

    $query = "SELECT q.*, u.first_name, u.last_name, u.profile_pic 
              FROM quests q 
              JOIN users u ON q.faculty_id = u.id 
              WHERE q.status = 'active' 
              ORDER BY q.created_at DESC";
    
    $result = $conn->query($query);
    
    if ($result === false) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $quests = [];
    while ($row = $result->fetch_assoc()) {
        $quests[] = [
            'id' => $row['id'],
            'faculty_name' => $row['first_name'] . ' ' . $row['last_name'],
            'faculty_pic' => $row['profile_pic'] ?: 'images/default_avatar.png',
            'description' => $row['description'],
            'jobType' => $row['job_type'],
            'created_at' => $row['created_at'],
            'rewards' => [
                'cash' => $row['cash_reward'],
                'snack' => $row['snack_reward'] == 1
            ]
        ];
    }
    
    ob_end_clean();
    sendJsonResponse([
        'success' => true,
        'quests' => $quests
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    error_log("Error in get_available_quests.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
} 