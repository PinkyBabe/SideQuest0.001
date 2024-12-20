<?php
// Turn off error display in production
ini_set('display_errors', 0);
error_reporting(0);

if (!function_exists('sanitize')) {
    function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date) {
        return date('M d, Y h:i A', strtotime($date));
    }
}

if (!function_exists('isAjaxRequest')) {
    function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

if (!function_exists('handleError')) {
    function handleError($message, $error_code = 500) {
        error_log("Error handled: " . $message);
        http_response_code($error_code);
        if (!headers_sent()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
        }
        exit;
    }
}

if (!function_exists('checkRequiredTables')) {
    function checkRequiredTables() {
        try {
            $conn = Database::getInstance();
            $required_tables = ['users', 'quests', 'quest_rewards'];
            
            foreach ($required_tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows === 0) {
                    throw new Exception("Required table '$table' does not exist");
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Database table check failed: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('initializeDatabase')) {
    function initializeDatabase() {
        try {
            if (!checkRequiredTables()) {
                $sql_file = file_get_contents(__DIR__ . '/../sidequest_db.sql');
                if ($sql_file === false) {
                    throw new Exception("Could not read SQL file");
                }
                
                $conn = Database::getInstance();
                if ($conn->multi_query($sql_file)) {
                    do {
                        while ($conn->more_results() && $conn->next_result()) {;}
                    } while ($conn->more_results());
                }
                
                if ($conn->error) {
                    throw new Exception("Error initializing database: " . $conn->error);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Database initialization failed: " . $e->getMessage());
            return false;
        }
    }
}

// Stats functions for admin dashboard
if (!function_exists('getFacultyCount')) {
    function getFacultyCount() {
        try {
            $conn = Database::getInstance();
            $query = "SELECT COUNT(*) as count FROM users WHERE role = 'faculty'";
            $result = $conn->query($query);
            if ($result) {
                return $result->fetch_assoc()['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error getting faculty count: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getStudentCount')) {
    function getStudentCount() {
        try {
            $conn = Database::getInstance();
            $query = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
            $result = $conn->query($query);
            if ($result) {
                return $result->fetch_assoc()['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error getting student count: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getActivePostsCount')) {
    function getActivePostsCount() {
        try {
            $conn = Database::getInstance();
            $query = "SELECT COUNT(*) as count FROM quests WHERE status = 'active'";
            $result = $conn->query($query);
            if ($result) {
                return $result->fetch_assoc()['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error getting active posts count: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getCompletedTasksCount')) {
    function getCompletedTasksCount() {
        try {
            $conn = Database::getInstance();
            $query = "SELECT COUNT(*) as count FROM quests WHERE status = 'completed'";
            $result = $conn->query($query);
            if ($result) {
                return $result->fetch_assoc()['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error getting completed tasks count: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getFacultyList')) {
    function getFacultyList() {
        try {
            $conn = Database::getInstance();
            $query = "
                SELECT 
                    id, 
                    first_name, 
                    last_name, 
                    email, 
                    office_name,
                    room_number,
                    is_active
                FROM users 
                WHERE role = 'faculty'
                ORDER BY last_name, first_name
            ";
            $result = $conn->query($query);
            
            $faculty = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $faculty[] = $row;
                }
            }
            return $faculty;
        } catch (Exception $e) {
            error_log("Error getting faculty list: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getStudentList')) {
    function getStudentList() {
        try {
            $conn = Database::getInstance();
            $query = "
                SELECT 
                    id, 
                    first_name, 
                    last_name, 
                    email, 
                    year_level,
                    is_active
                FROM users 
                WHERE role = 'student'
                ORDER BY last_name, first_name
            ";
            $result = $conn->query($query);
            
            $students = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $students[] = $row;
                }
            }
            return $students;
        } catch (Exception $e) {
            error_log("Error getting student list: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getDefaultAvatar')) {
    function getDefaultAvatar() {
        return 'https://tse2.mm.bing.net/th?id=OIP.yYUwl3GDU07Q5J5ttyW9fQHaHa&pid=Api&P=0&h=220';
    }
}
?>