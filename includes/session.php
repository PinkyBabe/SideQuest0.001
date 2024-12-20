<?php
// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Turn off error display
ini_set('display_errors', 0);
error_reporting(0); 