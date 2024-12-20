<?php
require_once 'config.php';
require_once 'functions.php';

session_start();
$student_id = $_SESSION['user_id'];
$quest_id = $_GET['id'];

$conn = Database::getInstance();
$query = "SELECT q.*, u.first_name, u.last_name, u.email, u.office, u.room
          FROM quests q 
          JOIN users u ON q.faculty_id = u.id
          WHERE q.id = ? AND q.student_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $quest_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
$quest = $result->fetch_assoc();

if ($quest) {
    $quest['faculty_name'] = $quest['first_name'] . ' ' . $quest['last_name'];
    unset($quest['first_name'], $quest['last_name']);
}

header('Content-Type: application/json');
echo json_encode($quest); 