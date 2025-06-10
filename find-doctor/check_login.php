<?php
// check_login.php - Simple login status checker

// Start session
session_start();

// Prevent PHP notices and warnings from breaking the JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set the content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// You can also check for other session variables that indicate login
if (!$logged_in) {
    // Check alternative session variables that might indicate login
    $logged_in = (
        isset($_SESSION['user_id']) ||
        isset($_SESSION['id']) ||
        isset($_SESSION['userId']) ||
        isset($_SESSION['user_email']) ||
        isset($_SESSION['email'])
    );
}

// Return the login status
echo json_encode([
    'logged_in' => $logged_in,
    'user_id' => $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['userId'] ?? null,
    'user_email' => $_SESSION['user_email'] ?? $_SESSION['email'] ?? null,
    'user_name' => $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? null,
    'timestamp' => time()
]);
?>