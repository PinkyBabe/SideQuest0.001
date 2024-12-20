<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only define these functions if they don't already exist
if (!function_exists('checkUserLogin')) {
    function checkUserLogin() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }
    }
}

if (!function_exists('checkUserRole')) {
    function checkUserRole($allowed_roles) {
        error_log("Checking user role. Session data: " . print_r($_SESSION, true));
        
        if (!isset($_SESSION['user_id'])) {
            error_log("No user_id in session");
            if (isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                exit;
            }
            header('Location: ' . BASE_URL . '/login.php');
            exit();
        }

        try {
            $conn = Database::getInstance();
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            
            if (!$stmt) {
                error_log("Database error in auth_middleware: " . $conn->error);
                throw new Exception("Database error");
            }
            
            $stmt->bind_param("i", $_SESSION['user_id']);
            
            if (!$stmt->execute()) {
                error_log("Query execution failed in auth_middleware: " . $stmt->error);
                throw new Exception("Query failed");
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("No user found with ID: " . $_SESSION['user_id']);
                session_destroy();
                if (isAjaxRequest()) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                    exit;
                }
                header('Location: ' . BASE_URL . '/login.php');
                exit();
            }
            
            $user = $result->fetch_assoc();
            error_log("User role from database: " . $user['role']);
            
            if (!in_array($user['role'], $allowed_roles)) {
                error_log("User role not allowed. User role: " . $user['role'] . ", Allowed roles: " . implode(', ', $allowed_roles));
                if (isAjaxRequest()) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
                    exit;
                }
                header('Location: ' . BASE_URL . '/login.php');
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Auth middleware error: " . $e->getMessage());
            if (isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Server error']);
                exit;
            }
            header('Location: ' . BASE_URL . '/login.php');
            exit();
        }
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Check if user is logged in
checkUserLogin();
?> 