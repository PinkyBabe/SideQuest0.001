<?php
require_once 'config.php';
require_once 'auth_middleware.php';

checkUserRole(['student']);

$conn = Database::getInstance();
$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $quest_id = $data['quest_id'] ?? null;
    $student_id = $_SESSION['user_id'];

    if (!$quest_id) {
        throw new Exception('Quest ID is required');
    }

    // Check if quest belongs to student
    $check = $conn->prepare("SELECT id FROM user_quests WHERE quest_id = ? AND student_id = ? AND status = 'accepted'");
    $check->bind_param('ii', $quest_id, $student_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Quest not found or not accepted');
    }

    // Update quest status
    $stmt = $conn->prepare("UPDATE user_quests SET status = 'completed', completed_at = NOW() WHERE quest_id = ? AND student_id = ?");
    $stmt->bind_param('ii', $quest_id, $student_id);
    
    if ($stmt->execute()) {
        // Update post status
        $update = $conn->prepare("UPDATE posts SET status = 'completed' WHERE id = ?");
        $update->bind_param('i', $quest_id);
        $update->execute();
        
        $response['success'] = true;
        $response['message'] = 'Work submitted successfully';
    } else {
        throw new Exception('Failed to submit work');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 