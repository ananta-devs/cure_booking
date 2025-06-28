<?php
// session_check.php - Include this at the top of doctor dashboard pages
session_start();

function checkDoctorSession($redirectOnFail = true) {
    $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    $isDoctor = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'doctor';
    $hasDoctorId = isset($_SESSION['doctor_id']) && !empty($_SESSION['doctor_id']);
    
    // Check session timeout (optional - 24 hours)
    $sessionTimeout = 24 * 60 * 60; // 24 hours in seconds
    $isSessionValid = true;
    
    if (isset($_SESSION['login_time'])) {
        $isSessionValid = (time() - $_SESSION['login_time']) < $sessionTimeout;
    }
    
    $sessionValid = $isLoggedIn && $isDoctor && $hasDoctorId && $isSessionValid;
    
    if (!$sessionValid) {
        if (!$isSessionValid) {
            // Session expired
            session_unset();
            session_destroy();
            
            if ($redirectOnFail) {
                header("Location: ../login.php?error=session_expired");
                exit;
            }
        } else if ($redirectOnFail) {
            // Not logged in or not a doctor
            header("Location: ../login.php?error=access_denied");
            exit;
        }
        
        return false;
    }
    
    return [
        'doctor_id' => $_SESSION['doctor_id'],
        'doctor_name' => $_SESSION['doctor_name'] ?? '',
        'doctor_email' => $_SESSION['doctor_email'] ?? '',
        'doctor_specialty' => $_SESSION['doctor_specialty'] ?? '',
        'doctor_image' => $_SESSION['doctor_image'] ?? ''
    ];
}

// Function to get logged-in doctor info
function getLoggedInDoctorInfo() {
    return checkDoctorSession(false);
}

// Auto-check session if accessed directly (not just included)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $doctorInfo = checkDoctorSession();
    if ($doctorInfo) {
        echo json_encode([
            'success' => true,
            'doctor_info' => $doctorInfo,
            'message' => 'Valid doctor session'
        ]);
    }
}
?>