<?php
require_once 'config.php';
require_once 'auth_middleware.php';

checkUserRole(['student']);

$conn = Database::getInstance();
$response = ['success' => false, 'quests' => [], 'message' => ''];

try {
    $student_id = $_SESSION['user_id'];
    
    $query = "SELECT p.*, u.first_name, u.last_name, u.profile_pic, uq.status as quest_status, uq.accepted_at
              FROM user_quests uq
              JOIN posts p ON uq.quest_id = p.id
              JOIN users u ON p.faculty_id = u.id
              WHERE uq.student_id = ?
              ORDER BY uq.accepted_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $quests = [];
        while ($row = $result->fetch_assoc()) {
            $quests[] = [
                'id' => $row['id'],
                'faculty_name' => $row['first_name'] . ' ' . $row['last_name'],
                'faculty_pic' => $row['profile_pic'],
                'description' => $row['description'],
                'jobType' => $row['job_type'],
                'status' => $row['quest_status'],
                'accepted_at' => $row['accepted_at'],
                'rewards' => [
                    'cash' => $row['cash_reward'],
                    'snack' => $row['snack_reward'] == 1
                ]
            ];
        }
        $response['success'] = true;
        $response['quests'] = $quests;
    } else {
        throw new Exception("Error fetching quests");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 