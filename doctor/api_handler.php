<?php

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
            'redirect' => 'http://localhost/cure_booking/login.php'
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
            // Debug: Log the doctor_id being searched
            error_log("Searching for doctor with ID: " . $doctor_id);
            
            // Updated to use doc_id as per database schema
            $stmt = $this->pdo->prepare("SELECT * FROM doctor WHERE doc_id = ?");
            $stmt->execute([$doctor_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug: Log the result
            if ($result) {
                error_log("Doctor found: " . $result['doc_name']);
            } else {
                error_log("No doctor found with ID: " . $doctor_id);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Database error in getDoctorById: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDoctorData() {
        // Debug: Log session data
        error_log("Session data: " . print_r($_SESSION, true));
        
        // Check if doctor_id exists in session
        if (!isset($_SESSION['doctor_id'])) {
            error_log("No doctor_id in session");
            redirectToLogin('Session invalid - no doctor ID found');
        }
        
        // Use doctor ID from session instead of URL parameter for security
        $doctor_id = $_SESSION['doctor_id'];
        
        // Fetch doctor details
        $doctor = $this->getDoctorById($doctor_id);
        
        if (!$doctor) {
            error_log("Doctor not found in database for ID: " . $doctor_id);
            redirectToLogin('Doctor account not found in database');
        }
        
        // Get doctor-clinic assignments and availability
        $availability = $this->getDoctorAvailability($doctor_id);
        
        return [
            'doctor_id' => $doctor_id,
            'doctor' => $doctor,
            'availability' => $availability
        ];
    }
    
    public function getDoctorAvailability($doctor_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT dca.availability_schedule, c.clinic_name, c.clinic_id
                FROM doctor_clinic_assignments dca
                LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id
                WHERE dca.doctor_id = ?
            ");
            $stmt->execute([$doctor_id]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $availability = [];
            foreach ($assignments as $assignment) {
                $schedule = [];
                if (!empty($assignment['availability_schedule'])) {
                    $decoded = json_decode($assignment['availability_schedule'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $schedule = $decoded;
                    }
                }
                
                $availability[] = [
                    'clinic_id' => $assignment['clinic_id'],
                    'clinic_name' => $assignment['clinic_name'],
                    'schedule' => $schedule
                ];
            }
            
            return $availability;
        } catch (PDOException $e) {
            error_log("Database error in getDoctorAvailability: " . $e->getMessage());
            return [];
        }
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
                // Verify current password using doc_id
                $stmt = $this->pdo->prepare("SELECT doc_pass FROM doctor WHERE doc_id = ?");
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
                        // Update password with proper hashing using doc_id
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $this->pdo->prepare("UPDATE doctor SET doc_pass = ? WHERE doc_id = ?");
                        
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
                        // Update using doc_id
                        $stmt = $this->pdo->prepare("UPDATE doctor SET doc_img = ? WHERE doc_id = ?");
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
                              AND status IN ('pending', 'confirmed')";
            $stmt_upcoming = $this->pdo->prepare($query_upcoming);
            $stmt_upcoming->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
            $stmt_upcoming->bindParam(':today', $today);
            $stmt_upcoming->bindParam(':current_time', $current_time);
            $stmt_upcoming->execute();
            $upcoming_today = $stmt_upcoming->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Get total appointments count
            $query_total = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = :doctor_id";
            $stmt_total = $this->pdo->prepare($query_total);
            $stmt_total->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
            $stmt_total->execute();
            $total_appointments = $stmt_total->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Get pending appointments count
            $query_pending = "SELECT COUNT(*) as count FROM appointments 
                             WHERE doctor_id = :doctor_id AND status = 'pending'";
            $stmt_pending = $this->pdo->prepare($query_pending);
            $stmt_pending->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
            $stmt_pending->execute();
            $pending_appointments = $stmt_pending->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Return success with stats
            echo json_encode([
                'success' => true,
                'today_bookings' => (int)$today_bookings,
                'tomorrow_bookings' => (int)$tomorrow_bookings,
                'upcoming_today' => (int)$upcoming_today,
                'total_appointments' => (int)$total_appointments,
                'pending_appointments' => (int)$pending_appointments
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
    
    public function getAppointments($limit = null, $status = null) {
        try {
            $doctor_id = $_SESSION['doctor_id'];
            
            $query = "SELECT a.*, 
                             DATE_FORMAT(a.appointment_date, '%Y-%m-%d') as formatted_date,
                             TIME_FORMAT(a.appointment_time, '%H:%i') as formatted_time
                      FROM appointments a 
                      WHERE a.doctor_id = :doctor_id";
            
            $params = [':doctor_id' => $doctor_id];
            
            if ($status) {
                $query .= " AND a.status = :status";
                $params[':status'] = $status;
            }
            
            $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit";
                $params[':limit'] = $limit;
            }
            
            $stmt = $this->pdo->prepare($query);
            
            // Bind parameters properly
            foreach ($params as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Database error in getAppointments: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateAppointmentStatus() {
        header('Content-Type: application/json');
        
        if (!checkDoctorSession()) {
            echo json_encode([
                'success' => false,
                'message' => 'Session expired. Please login again.',
                'redirect' => 'http://localhost/cure_booking/login.php'
            ]);
            exit;
        }
        
        $appointment_id = $_POST['appointment_id'] ?? '';
        $new_status = $_POST['status'] ?? '';
        $doctor_id = $_SESSION['doctor_id'];
        
        $response = ['success' => false, 'message' => ''];
        
        // Validate input
        $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];
        if (empty($appointment_id) || empty($new_status)) {
            $response['message'] = 'Missing required parameters.';
        } elseif (!in_array($new_status, $valid_statuses)) {
            $response['message'] = 'Invalid status value.';
        } else {
            try {
                // Verify appointment belongs to this doctor
                $stmt = $this->pdo->prepare("SELECT id FROM appointments WHERE id = ? AND doctor_id = ?");
                $stmt->execute([$appointment_id, $doctor_id]);
                
                if ($stmt->fetch()) {
                    // Update the appointment status
                    $stmt = $this->pdo->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ? AND doctor_id = ?");
                    
                    if ($stmt->execute([$new_status, $appointment_id, $doctor_id])) {
                        $response['success'] = true;
                        $response['message'] = 'Appointment status updated successfully!';
                    } else {
                        $response['message'] = 'Failed to update appointment status.';
                    }
                } else {
                    $response['message'] = 'Appointment not found or access denied.';
                }
            } catch (PDOException $e) {
                error_log("Database error in updateAppointmentStatus: " . $e->getMessage());
                $response['message'] = 'Database error occurred. Please try again.';
            }
        }
        
        echo json_encode($response);
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

// Debug: Check if we have a PDO connection
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
        
    case 'update_appointment_status':
        $doctorController->updateAppointmentStatus();
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
            
            // Get recent appointments for dashboard
            $recent_appointments = $doctorController->getAppointments(10);
            $pending_appointments = $doctorController->getAppointments(null, 'pending');
            
            // Debug: Log successful data retrieval
            error_log("Successfully retrieved doctor data for ID: " . $doctor_id);
            
        } catch (Exception $e) {
            error_log("Error in view_profile: " . $e->getMessage());
            redirectToLogin('Error loading profile data');
        }
        break;
}
?>