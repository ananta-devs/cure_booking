<?php
    // php/logout.php
    session_start();

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header("Location: http://localhost/cure_booking/adminhub/login.php");
    exit;
?>