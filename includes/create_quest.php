<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    error_log("Starting create_quest.php");
    error_log("Session data: " . print_r($_SESSION, true));

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    error_log("User ID: " . $_SESSION['user_id']);

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Received data: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    $required_fields = ['description', 'jobType', 'location', 'meetingTime', 'estimatedHours'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $faculty_id = $_SESSION['user_id'];
    $conn = Database::getInstance();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert quest with correct column names
        $query = "
            INSERT INTO quests (
                faculty_id,
                description,
                job_type,
                location,
                meeting_time,
                estimated_hours,
                cash_reward,
                snack_reward,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ";
        
        error_log("Preparing query: " . $query);
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Calculate rewards
        $cash_reward = null;
        $snack_reward = 0;
        
        if (isset($data['rewards'])) {
            if ($data['rewards']['type'] === 'cash' || $data['rewards']['type'] === 'both') {
                $cash_reward = $data['rewards']['cash'];
            }
            if ($data['rewards']['type'] === 'food' || $data['rewards']['type'] === 'both') {
                $snack_reward = 1;
            }
        }

        error_log("Binding parameters...");
        $stmt->bind_param(
            "issssidb",
            $faculty_id,
            $data['description'],
            $data['jobType'],
            $data['location'],
            $data['meetingTime'],
            $data['estimatedHours'],
            $cash_reward,
            $snack_reward
        );
        
        error_log("Executing statement...");
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $quest_id = $conn->insert_id;
        error_log("Quest created with ID: " . $quest_id);

        // Commit transaction
        $conn->commit();
        error_log("Transaction committed");

        echo json_encode([
            'success' => true,
            'message' => 'Quest created successfully',
            'quest_id' => $quest_id
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction rolled back: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in create_quest.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 