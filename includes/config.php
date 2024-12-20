<?php
// Start session and suppress errors from showing in output
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $database = 'sidequest_db';
            
            $this->connection = new mysqli($host, $username, $password, $database);
            
            if ($this->connection->connect_error) {
                error_log("Database connection failed: " . $this->connection->connect_error);
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
            error_log("Database connection successful");
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
}

// Constants
define('BASE_URL', 'http://localhost/sidequest');

// Timezone
date_default_timezone_set('Asia/Manila');
?>