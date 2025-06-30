<?php
// logout.php - Handles logout for both doctors and clinics
session_start();

// Clear all session data
$_SESSION = array();

// Delete the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Determine redirect URL based on user type
$redirectUrl = 'http://localhost/cure_booking/index.php'; // Default



// Prevent caching and redirect
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Location: " . $redirectUrl);
exit;
?>