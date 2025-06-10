<?php
session_start();
require_once 'config.php';

// Session Protection Functions
function checkDoctorSession() {
    // Check if doctor is logged in
    if (!isset($_SESSION['doctor_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check if user type is doctor
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'doctor') {
        return false;
    }
    
    // Optional: Check session timeout (24 hours)
    if (isset($_SESSION['login_time'])) {
        $session_timeout = 24 * 60 * 60; // 24 hours in seconds
        if ((time() - $_SESSION['login_time']) > $session_timeout) {
            return false;
        }
    }
    
    return true;
}

function redirectToLogin($message = 'Please login to access this page') {
    // Clear any existing session data
    session_unset();
    session_destroy();
    
    // If it's an AJAX request, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'redirect' => 'http://localhostcure_booking/login.php'
        ]);
        exit;
    }
    
    // For regular requests, redirect to login page
    header('Location: http://localhost/cure_booking/login.php?message=' . urlencode($message));
    exit;
}

function performSecureLogout() {
    // Unset all session variables
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
}

class DoctorController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getDoctorById($doctor_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM doctor WHERE id = ?");
            $stmt->execute([$doctor_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getDoctorById: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDoctorData() {
        // Use doctor ID from session instead of URL parameter for security
        $doctor_id = $_SESSION['doctor_id'];
        
        // Fetch doctor details
        $doctor = $this->getDoctorById($doctor_id);
        
        if (!$doctor) {
            redirectToLogin('Doctor account not found');
        }
        
        // Safe JSON decode with error handling
        $availability = [];
        if (!empty($doctor['availability'])) {
            $decoded = json_decode($doctor['availability'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $availability = $decoded;
            }
        }
        
        return [
            'doctor_id' => $doctor_id,
            'doctor' => $doctor,
            'availability' => $availability
        ];
    }
    
    public function handlePasswordChange() {
        // Set proper content type for AJAX response
        header('Content-Type: application/json');
        
        // Additional session check for sensitive operations
        if (!checkDoctorSession()) {
            echo json_encode([
                'success' => false,
                'message' => 'Session expired. Please login again.',
                'redirect' => 'http://localhost/cure_booking/login.php'
            ]);
            exit;
        }
        
        // Use doctor ID from session instead of POST data for security
        $doctor_id = $_SESSION['doctor_id'];
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $response = ['success' => false, 'message' => ''];
        
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $response['message'] = 'All fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $response['message'] = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $response['message'] = 'New password must be at least 6 characters long.';
        } else {
            try {
                // Verify current password
                $stmt = $this->pdo->prepare("SELECT doc_pass FROM doctor WHERE id = ?");
                $stmt->execute([$doctor_id]);
                $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($doctor) {
                    // Try both hashed and plain text password verification (same as login)
                    $storedPassword = $doctor['doc_pass'];
                    $passwordMatch = false;
                    
                    // First try password_verify (for hashed passwords)
                    if (password_verify($current_password, $storedPassword)) {
                        $passwordMatch = true;
                    }
                    // If that fails, try direct comparison (for plain text passwords)
                    else if ($current_password === $storedPassword) {
                        $passwordMatch = true;
                    }
                    
                    if ($passwordMatch) {
                        // Update password with proper hashing
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $this->pdo->prepare("UPDATE doctor SET doc_pass = ? WHERE id = ?");
                        
                        if ($stmt->execute([$hashed_password, $doctor_id])) {
                            $response['success'] = true;
                            $response['message'] = 'Password changed successfully!';
                        } else {
                            $response['message'] = 'Failed to update password. Please try again.';
                        }
                    } else {
                        $response['message'] = 'Current password is incorrect.';
                    }
                } else {
                    $response['message'] = 'Doctor account not found.';
                }
            } catch (PDOException $e) {
                error_log("Database error in handlePasswordChange: " . $e->getMessage());
                $response['message'] = 'Database error occurred. Please try again.';
            }
        }
        
        echo json_encode($response);
        exit;
    }
    
    public function handleImageUpload() {
        // Set proper content type for AJAX response
        header('Content-Type: application/json');
        
        // Additional session check for sensitive operations
        if (!checkDoctorSession()) {
            echo json_encode([
                'success' => false,
                'message' => 'Session expired. Please login again.',
                'redirect' => 'http://localhost/cure_booking/login.php'
            ]);
            exit;
        }
        
        // Use doctor ID from session instead of POST data for security
        $doctor_id = $_SESSION['doctor_id'];
        $response = ['success' => false, 'message' => '', 'image_path' => ''];
        
        if (isset($_FILES['doctor_image']) && $_FILES['doctor_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'http://localhost/adminhub/manage-doctors/uploads/';
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            $fileType = $_FILES['doctor_image']['type'];
            $fileSize = $_FILES['doctor_image']['size'];
            $tmpName = $_FILES['doctor_image']['tmp_name'];
            
            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                $response['message'] = 'Invalid file type. Please upload JPG, PNG, or GIF images.';
            } 
            // Validate file size
            elseif ($fileSize > $maxSize) {
                $response['message'] = 'File size too large. Maximum size is 5MB.';
            } 
            // Proceed with upload
            else {
                $extension = pathinfo($_FILES['doctor_image']['name'], PATHINFO_EXTENSION);
                $filename = 'doctor_' . $doctor_id . '_' . time() . '.' . $extension;
                $targetPath = $_SERVER['DOCUMENT_ROOT'] . '/adminhub/manage-doctors/uploads/' . $filename;
                
                // Create directory if it doesn't exist
                $uploadDirectory = dirname($targetPath);
                if (!is_dir($uploadDirectory)) {
                    if (!mkdir($uploadDirectory, 0755, true)) {
                        $response['message'] = 'Failed to create upload directory.';
                        echo json_encode($response);
                        exit;
                    }
                }
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    try {
                        $stmt = $this->pdo->prepare("UPDATE doctor SET doc_img = ? WHERE id = ?");
                        if ($stmt->execute([$filename, $doctor_id])) {
                            // Update session data
                            $_SESSION['doctor_image'] = $filename;
                            
                            $response['success'] = true;
                            $response['message'] = 'Image uploaded successfully!';
                            $response['image_path'] = $uploadDir . $filename;
                        } else {
                            $response['message'] = 'Database update failed.';
                            // Clean up uploaded file if database update fails
                            if (file_exists($targetPath)) {
                                unlink($targetPath);
                            }
                        }
                    } catch (PDOException $e) {
                        error_log("Database error in handleImageUpload: " . $e->getMessage());
                        $response['message'] = 'Database error occurred.';
                        // Clean up uploaded file
                        if (file_exists($targetPath)) {
                            unlink($targetPath);
                        }
                    }
                } else {
                    $response['message'] = 'Failed to upload file.';
                }
            }
        } else {
            // Handle different upload errors
            $error_code = $_FILES['doctor_image']['error'] ?? UPLOAD_ERR_NO_FILE;
            switch ($error_code) {
                case UPLOAD_ERR_NO_FILE:
                    $response['message'] = 'No file was uploaded.';
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $response['message'] = 'File is too large.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $response['message'] = 'File was only partially uploaded.';
                    break;
                default:
                    $response['message'] = 'Upload error occurred.';
            }
        }
        
        echo json_encode($response);
        exit;
    }
    
    public function getBookingStats() {
        header('Content-Type: application/json');
        
        // Check session
        if (!checkDoctorSession()) {
            echo json_encode([
                'success' => false,
                'message' => 'Session expired. Please login again.',
                'redirect' => 'http://localhost/cure_booking/login.php'
            ]);
            exit;
        }
        
        try {
            // Get doctor ID from session
            $doctor_id = $_SESSION['doctor_id'];
            
            // Current date and time
            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $current_time = date('H:i:s');
            
            // Get today's bookings
            $query_today = "SELECT COUNT(*) as count FROM appointments 
                           WHERE doctor_id = :doctor_id 
                           AND DATE(appointment_date) = :today";
            $stmt_today = $this->pdo->prepare($query_today);
            $stmt_today->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
            $stmt_today->bindParam(':today', $today);
            $stmt_today->execute();
            $today_bookings = $stmt_today->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Get tomorrow's bookings
            $query_tomorrow = "SELECT COUNT(*) as count FROM appointments 
                              WHERE doctor_id = :doctor_id 
                              AND DATE(appointment_date) = :tomorrow";
            $stmt_tomorrow = $this->pdo->prepare($query_tomorrow);
            $stmt_tomorrow->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
            $stmt_tomorrow->bindParam(':tomorrow', $tomorrow);
            $stmt_tomorrow->execute();
            $tomorrow_bookings = $stmt_tomorrow->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Get upcoming appointments for today (after current time)
            $query_upcoming = "SELECT COUNT(*) as count FROM appointments 
                              WHERE doctor_id = :doctor_id 
                              AND DATE(appointment_date) = :today 
                              AND TIME(appointment_time) > :current_time
                              AND status = 'Accepted'";
            $stmt_upcoming = $this->pdo->prepare($query_upcoming);
            $stmt_upcoming->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
            $stmt_upcoming->bindParam(':today', $today);
            $stmt_upcoming->bindParam(':current_time', $current_time);
            $stmt_upcoming->execute();
            $upcoming_today = $stmt_upcoming->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Return success with stats
            echo json_encode([
                'success' => true,
                'today_bookings' => (int)$today_bookings,
                'tomorrow_bookings' => (int)$tomorrow_bookings,
                'upcoming_today' => (int)$upcoming_today
            ]);
            
        } catch (PDOException $e) {
            error_log("Database error in getBookingStats: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Database error occurred while fetching booking statistics.'
            ]);
        } catch (Exception $e) {
            error_log("General error in getBookingStats: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while fetching booking statistics.'
            ]);
        }
        exit;
    }
    
    public function handleLogout() {
        // Perform secure logout
        performSecureLogout();
        
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'You have been successfully logged out.',
                'redirect' => 'http://localhost/cure_booking/login.php'
            ]);
            exit;
        }
        
        // For regular requests, redirect to login page
        header('Location: http://localhost/cure_booking/login.php?message=' . urlencode('You have been successfully logged out.'));
        exit;
    }
}

// Check if PDO connection exists
if (!isset($pdo)) {
    error_log("PDO connection not available");
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error'
        ]);
        exit;
    } else {
        die("Database connection error. Please check your configuration.");
    }
}

// Initialize controller
$doctorController = new DoctorController($pdo);

// Main request handler using switch case
$action = '';

// Determine action from POST, GET, or direct access
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_stats'])) {
    $action = 'get_booking_stats';
} elseif (basename($_SERVER['PHP_SELF']) === 'logout.php' || 
         (isset($_GET['logout']) && $_GET['logout'] === '1')) {
    $action = 'logout';
} else {
    $action = 'view_profile'; // Default action for profile viewing
}

// Handle all actions using switch case
switch ($action) {
    case 'change_password':
        // Protect the entire page - check session before any operations
        if (!checkDoctorSession()) {
            redirectToLogin('Your session has expired. Please login again.');
        }
        $doctorController->handlePasswordChange();
        break;
        
    case 'upload_image':
        // Protect the entire page - check session before any operations
        if (!checkDoctorSession()) {
            redirectToLogin('Your session has expired. Please login again.');
        }
        $doctorController->handleImageUpload();
        break;
        
    case 'get_booking_stats':
        $doctorController->getBookingStats();
        break;
        
    case 'logout':
        $doctorController->handleLogout();
        break;
        
    case 'view_profile':
    default:
        // Protect the entire page - check session before any operations
        if (!checkDoctorSession()) {
            redirectToLogin('Your session has expired. Please login again.');
        }
        
        // If not an API request, prepare data for the view
        try {
            $doctorData = $doctorController->getDoctorData();
            
            // Extract variables for the view
            $doctor_id = $doctorData['doctor_id'];
            $doctor = $doctorData['doctor'];
            $availability = $doctorData['availability'];
        } catch (Exception $e) {
            error_log("Error in view_profile: " . $e->getMessage());
            redirectToLogin('Error loading profile data');
        }
        break;
}
?>