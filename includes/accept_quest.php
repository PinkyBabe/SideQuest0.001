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

    // Start transaction
    $conn->begin_transaction();

    // Check if quest is still available
    $check = $conn->prepare("SELECT status FROM quests WHERE id = ? AND status = 'active'");
    $check->bind_param('i', $quest_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Quest is no longer available');
    }

    // Insert into user_quests
    $stmt = $conn->prepare("INSERT INTO user_quests (quest_id, student_id, status) VALUES (?, ?, 'accepted')");
    $stmt->bind_param('ii', $quest_id, $student_id);
    
    if ($stmt->execute()) {
        // Update quest status
        $update = $conn->prepare("UPDATE quests SET status = 'in_progress' WHERE id = ?");
        $update->bind_param('i', $quest_id);
        
        if ($update->execute()) {
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Quest accepted successfully';
        } else {
            throw new Exception('Failed to update quest status');
        }
    } else {
        throw new Exception('Failed to accept quest');
    }
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 