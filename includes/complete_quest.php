<?php
require_once 'config.php';
require_once 'functions.php';

session_start();
$student_id = $_SESSION['user_id'];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$quest_id = $data['quest_id'];

$conn = Database::getInstance();

// Verify the quest belongs to this student
$query = "UPDATE quests 
          SET status = 'completed', 
              completion_date = NOW() 
          WHERE id = ? AND student_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $quest_id, $student_id);
$success = $stmt->execute();

$response = [
    'success' => $success,
    'message' => $success ? 'Quest completed successfully' : 'Error completing quest'
];

header('Content-Type: application/json');
echo json_encode($response); 