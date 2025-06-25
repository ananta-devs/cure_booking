<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page with a success message
session_start();
$_SESSION['success'] = "You have been logged out successfully.";

header("Location: ../index.php");
exit();
?>