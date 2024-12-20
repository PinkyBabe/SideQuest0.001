<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth_middleware.php';

header('Content-Type: application/json');

$conn = Database::getInstance();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_faculty_stats':
        $stats = [
            'pending' => getQuestsCount($user_id, 'pending'),
            'active' => getQuestsCount($user_id, 'accepted') + getQuestsCount($user_id, 'in_progress'),
            'completed' => getQuestsCount($user_id, 'completed')
        ];
        echo json_encode($stats);
        break;

    case 'get_student_stats':
        $stats = [
            'active' => getActiveQuestsCount($user_id, 'student'),
            'completed' => getCompletedQuestsCount($user_id, 'student'),
            'total_earnings' => getTotalEarnings($user_id)
        ];
        echo json_encode($stats);
        break;

    case 'get_faculty_quests':
        $status = $_GET['status'] ?? 'active';
        $quests = getFacultyQuests($user_id, $status);
        echo json_encode($quests);
        break;

    case 'get_student_quests':
        $status = $_GET['status'] ?? 'active';
        $quests = getStudentQuests($user_id, $status);
        echo json_encode($quests);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function getActiveQuestsCount($user_id, $type = 'faculty') {
    $conn = Database::getInstance();
    $condition = $type === 'faculty' ? "faculty_id = ?" : "student_id = ?";
    $stmt = $conn->prepare("SELECT COUNT(*) FROM quests WHERE $condition AND status = 'active'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function getCompletedQuestsCount($user_id, $type = 'faculty') {
    $conn = Database::getInstance();
    $condition = $type === 'faculty' ? "faculty_id = ?" : "student_id = ?";
    $stmt = $conn->prepare("SELECT COUNT(*) FROM quests WHERE $condition AND status = 'completed'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function getTotalStudentsCount($faculty_id) {
    $conn = Database::getInstance();
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT student_id) FROM quests WHERE faculty_id = ?");
    $stmt->bind_param('i', $faculty_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function getTotalEarnings($student_id) {
    $conn = Database::getInstance();
    $stmt = $conn->prepare("SELECT SUM(cash_reward) FROM quests WHERE student_id = ? AND status = 'completed'");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0] ?? 0;
}

function getQuestsCount($user_id, $status) {
    $conn = Database::getInstance();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM quests WHERE faculty_id = ? AND status = ?");
    $stmt->bind_param('is', $user_id, $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function getFacultyQuests($faculty_id, $status) {
    $conn = Database::getInstance();
    
    $statusCondition = $status === 'active' ? 
        "q.status IN ('accepted', 'in_progress')" : 
        "q.status = ?";
    
    $query = "
        SELECT q.*, 
               u.first_name, u.last_name, 
               c.code as course_code
        FROM quests q
        LEFT JOIN users u ON q.student_id = u.id
        LEFT JOIN courses c ON u.course_id = c.id
        WHERE q.faculty_id = ? AND $statusCondition
        ORDER BY q.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    
    if ($status === 'active') {
        $stmt->bind_param('i', $faculty_id);
    } else {
        $stmt->bind_param('is', $faculty_id, $status);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getStudentQuests($student_id, $status) {
    $conn = Database::getInstance();
    $stmt = $conn->prepare("
        SELECT q.*, u.first_name, u.last_name, u.department
        FROM quests q
        LEFT JOIN users u ON q.faculty_id = u.id
        WHERE q.student_id = ? AND q.status = ?
        ORDER BY q.created_at DESC
    ");
    $stmt->bind_param('is', $student_id, $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} 