<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Check if user is faculty
    checkUserRole(['faculty']);

    $faculty_id = $_SESSION['user_id'];
    $conn = Database::getInstance();
    
    // First, verify the faculty exists
    $checkFaculty = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'faculty'");
    if (!$checkFaculty) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $checkFaculty->bind_param("i", $faculty_id);
    if (!$checkFaculty->execute()) {
        throw new Exception("Execute failed: " . $checkFaculty->error);
    }
    
    $result = $checkFaculty->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Invalid faculty user");
    }

    // Get faculty's posts with rewards
    $query = "
        SELECT 
            q.*,
            u.first_name,
            u.last_name,
            u.office_name
        FROM quests q
        LEFT JOIN users u ON q.faculty_id = u.id
        WHERE q.faculty_id = ?
        ORDER BY q.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $faculty_id);
    if (!$stmt->execute()) {
        throw new Exception("Query failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $posts = [];
    
    while ($row = $result->fetch_assoc()) {
        $posts[] = [
            'id' => $row['id'],
            'description' => $row['description'],
            'jobType' => $row['job_type'],
            'location' => $row['location'],
            'meetingTime' => $row['meeting_time'],
            'estimatedHours' => $row['estimated_hours'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'faculty_name' => $row['first_name'] . ' ' . $row['last_name'],
            'department' => $row['office_name'],
            'rewards' => [
                'cash' => $row['cash_reward'],
                'snack' => $row['snack_reward'] == 1
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_faculty_posts.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 