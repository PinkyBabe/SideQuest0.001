<?php
require_once 'config.php';
require_once 'auth_middleware.php';
require_once 'helpers.php';

ob_start();

try {
    checkUserRole(['student']);
    
    $conn = Database::getInstance();
    $student_id = $_SESSION['user_id'];
    
    // Get completed quests count and total earnings
    $completed = $conn->prepare("
        SELECT 
            COUNT(*) as completed_count,
            COALESCE(SUM(q.cash_reward), 0) as total_earnings
        FROM user_quests uq
        JOIN quests q ON uq.quest_id = q.id
        WHERE uq.student_id = ? 
        AND uq.status = 'completed'
    ");
    
    if (!$completed) {
        throw new Exception("Failed to prepare completed quests query");
    }
    
    $completed->bind_param('i', $student_id);
    $completed->execute();
    $completed_result = $completed->get_result()->fetch_assoc();

    // Get active quests count
    $active = $conn->prepare("
        SELECT COUNT(*) as active_count
        FROM user_quests 
        WHERE student_id = ? 
        AND status IN ('accepted', 'in_progress')
    ");
    
    if (!$active) {
        throw new Exception("Failed to prepare active quests query");
    }
    
    $active->bind_param('i', $student_id);
    $active->execute();
    $active_count = $active->get_result()->fetch_assoc()['active_count'];

    ob_end_clean();
    
    sendJsonResponse([
        'success' => true,
        'stats' => [
            'completed' => (int)$completed_result['completed_count'],
            'active' => (int)$active_count,
            'earnings' => number_format((float)$completed_result['total_earnings'], 2)
        ]
    ]);

} catch (Exception $e) {
    ob_end_clean();
    error_log("Error in get_student_stats.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
} 