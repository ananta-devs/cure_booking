<?php

require_once 'config.php';

// Session Protection Functions
function checkDoctorSession() {
    if (!isset($_SESSION['doctor_id'], $_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'doctor') {
        return false;
    }
    
    // Check session timeout (24 hours)
    if (isset($_SESSION['login_time'])) {
        $session_timeout = 24 * 60 * 60;
        if ((time() - $_SESSION['login_time']) > $session_timeout) {
            return false;
        }
    }
    
    return true;
}

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirectToLogin($message = 'Please login to access this page') {
    session_unset();
    session_destroy();
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        sendJsonResponse([
            'success' => false,
            'message' => $message,
            'redirect' => 'http://localhost/cure_booking/login.php'
        ]);
    }
    
    header('Location: http://localhost/cure_booking/login.php?message=' . urlencode($message));
    exit;
}

function performSecureLogout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

class DoctorController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    private function validateSession() {
        if (!checkDoctorSession()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Session expired. Please login again.',
                'redirect' => 'http://localhost/cure_booking/login.php'
            ]);
        }
    }
    
    public function getDoctorById($doctor_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM doctor WHERE doc_id = ?");
            $stmt->execute([$doctor_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getDoctorById: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDoctorData() {
        if (!isset($_SESSION['doctor_id'])) {
            redirectToLogin('Session invalid - no doctor ID found');
        }
        
        $doctor_id = $_SESSION['doctor_id'];
        $doctor = $this->getDoctorById($doctor_id);
        
        if (!$doctor) {
            redirectToLogin('Doctor account not found in database');
        }
        
        return [
            'doctor_id' => $doctor_id,
            'doctor' => $doctor,
            'availability' => $this->getDoctorAvailability($doctor_id)
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
        $this->validateSession();
        
        $doctor_id = $_SESSION['doctor_id'];
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            sendJsonResponse(['success' => false, 'message' => 'All fields are required.']);
        }
        
        if ($new_password !== $confirm_password) {
            sendJsonResponse(['success' => false, 'message' => 'New passwords do not match.']);
        }
        
        if (strlen($new_password) < 6) {
            sendJsonResponse(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT doc_pass FROM doctor WHERE doc_id = ?");
            $stmt->execute([$doctor_id]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$doctor) {
                sendJsonResponse(['success' => false, 'message' => 'Doctor account not found.']);
            }
            
            $storedPassword = $doctor['doc_pass'];
            $passwordMatch = password_verify($current_password, $storedPassword) || $current_password === $storedPassword;
            
            if (!$passwordMatch) {
                sendJsonResponse(['success' => false, 'message' => 'Current password is incorrect.']);
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE doctor SET doc_pass = ? WHERE doc_id = ?");
            
            if ($stmt->execute([$hashed_password, $doctor_id])) {
                sendJsonResponse(['success' => true, 'message' => 'Password changed successfully!']);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Failed to update password. Please try again.']);
            }
            
        } catch (PDOException $e) {
            error_log("Database error in handlePasswordChange: " . $e->getMessage());
            sendJsonResponse(['success' => false, 'message' => 'Database error occurred. Please try again.']);
        }
    }
    
    public function handleImageUpload() {
        $this->validateSession();
        
        $doctor_id = $_SESSION['doctor_id'];
        
        if (!isset($_FILES['doctor_image']) || $_FILES['doctor_image']['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_INI_SIZE => 'File is too large.',
                UPLOAD_ERR_FORM_SIZE => 'File is too large.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.'
            ];
            
            $error_code = $_FILES['doctor_image']['error'] ?? UPLOAD_ERR_NO_FILE;
            $message = $error_messages[$error_code] ?? 'Upload error occurred.';
            sendJsonResponse(['success' => false, 'message' => $message]);
        }
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $fileType = $_FILES['doctor_image']['type'];
        $fileSize = $_FILES['doctor_image']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid file type. Please upload JPG, PNG, or GIF images.']);
        }
        
        if ($fileSize > $maxSize) {
            sendJsonResponse(['success' => false, 'message' => 'File size too large. Maximum size is 5MB.']);
        }
        
        $extension = pathinfo($_FILES['doctor_image']['name'], PATHINFO_EXTENSION);
        $filename = 'doctor_' . $doctor_id . '_' . time() . '.' . $extension;
        $targetPath = $_SERVER['DOCUMENT_ROOT'] . '/adminhub/manage-doctors/uploads/' . $filename;
        
        // Create directory if needed
        $uploadDirectory = dirname($targetPath);
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
            sendJsonResponse(['success' => false, 'message' => 'Failed to create upload directory.']);
        }
        
        if (!move_uploaded_file($_FILES['doctor_image']['tmp_name'], $targetPath)) {
            sendJsonResponse(['success' => false, 'message' => 'Failed to upload file.']);
        }
        
        try {
            $stmt = $this->pdo->prepare("UPDATE doctor SET doc_img = ? WHERE doc_id = ?");
            if ($stmt->execute([$filename, $doctor_id])) {
                $_SESSION['doctor_image'] = $filename;
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Image uploaded successfully!',
                    'image_path' => 'http://localhost/adminhub/manage-doctors/uploads/' . $filename
                ]);
            } else {
                unlink($targetPath); // Clean up on failure
                sendJsonResponse(['success' => false, 'message' => 'Database update failed.']);
            }
        } catch (PDOException $e) {
            error_log("Database error in handleImageUpload: " . $e->getMessage());
            unlink($targetPath); // Clean up on failure
            sendJsonResponse(['success' => false, 'message' => 'Database error occurred.']);
        }
    }
    
    public function getBookingStats() {
        $this->validateSession();
        
        try {
            $doctor_id = $_SESSION['doctor_id'];
            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $current_time = date('H:i:s');
            
            // Prepare all queries
            $queries = [
                'today_bookings' => "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ?",
                'tomorrow_bookings' => "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ?",
                'upcoming_today' => "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ? AND TIME(appointment_time) > ? AND status IN ('pending', 'confirmed')",
                'total_appointments' => "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ?",
                'pending_appointments' => "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND status = 'pending'"
            ];
            
            $results = [];
            
            foreach ($queries as $key => $query) {
                $stmt = $this->pdo->prepare($query);
                switch ($key) {
                    case 'today_bookings':
                        $stmt->execute([$doctor_id, $today]);
                        break;
                    case 'tomorrow_bookings':
                        $stmt->execute([$doctor_id, $tomorrow]);
                        break;
                    case 'upcoming_today':
                        $stmt->execute([$doctor_id, $today, $current_time]);
                        break;
                    case 'total_appointments':
                        $stmt->execute([$doctor_id]);
                        break;
                    case 'pending_appointments':
                        $stmt->execute([$doctor_id, 'pending']);
                        break;
                }
                $results[$key] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            }
            
            sendJsonResponse(array_merge(['success' => true], $results));
            
        } catch (PDOException $e) {
            error_log("Database error in getBookingStats: " . $e->getMessage());
            sendJsonResponse(['success' => false, 'message' => 'Database error occurred while fetching booking statistics.']);
        }
    }
    
    public function getAppointments($limit = null, $status = null) {
        try {
            $doctor_id = $_SESSION['doctor_id'];
            
            $query = "SELECT a.*, 
                             DATE_FORMAT(a.appointment_date, '%Y-%m-%d') as formatted_date,
                             TIME_FORMAT(a.appointment_time, '%H:%i') as formatted_time
                      FROM appointments a 
                      WHERE a.doctor_id = ?";
            
            $params = [$doctor_id];
            
            if ($status) {
                $query .= " AND a.status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
            
            if ($limit) {
                $query .= " LIMIT ?";
                $params[] = $limit;
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Database error in getAppointments: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateAppointmentStatus() {
        $this->validateSession();
        
        $appointment_id = $_POST['appointment_id'] ?? '';
        $new_status = $_POST['status'] ?? '';
        $doctor_id = $_SESSION['doctor_id'];
        
        $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];
        
        if (empty($appointment_id) || empty($new_status)) {
            sendJsonResponse(['success' => false, 'message' => 'Missing required parameters.']);
        }
        
        if (!in_array($new_status, $valid_statuses)) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid status value.']);
        }
        
        try {
            // Verify and update in one query
            $stmt = $this->pdo->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ? AND doctor_id = ?");
            
            if ($stmt->execute([$new_status, $appointment_id, $doctor_id])) {
                if ($stmt->rowCount() > 0) {
                    sendJsonResponse(['success' => true, 'message' => 'Appointment status updated successfully!']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Appointment not found or access denied.']);
                }
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Failed to update appointment status.']);
            }
        } catch (PDOException $e) {
            error_log("Database error in updateAppointmentStatus: " . $e->getMessage());
            sendJsonResponse(['success' => false, 'message' => 'Database error occurred. Please try again.']);
        }
    }
    
    public function handleLogout() {
        performSecureLogout();
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            sendJsonResponse([
                'success' => true,
                'message' => 'You have been successfully logged out.',
                'redirect' => 'http://localhost/cure_booking/login.php'
            ]);
        }
        
        header('Location: http://localhost/cure_booking/login.php?message=' . urlencode('You have been successfully logged out.'));
        exit;
    }
}

// Initialize
if (!isset($pdo)) {
    error_log("PDO connection not available");
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        sendJsonResponse(['success' => false, 'message' => 'Database connection error']);
    }
    die("Database connection error. Please check your configuration.");
}

$doctorController = new DoctorController($pdo);

// Determine action
$action = $_POST['action'] ?? $_GET['action'] ?? 
          (isset($_GET['get_stats']) ? 'get_booking_stats' : 
          (basename($_SERVER['PHP_SELF']) === 'logout.php' || isset($_GET['logout']) ? 'logout' : 'view_profile'));

// Route actions
switch ($action) {
    case 'change_password':
        $doctorController->handlePasswordChange();
        break;
    case 'upload_image':
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
        if (!checkDoctorSession()) {
            redirectToLogin('Your session has expired. Please login again.');
        }
        
        try {
            $doctorData = $doctorController->getDoctorData();
            extract($doctorData); // $doctor_id, $doctor, $availability
            
            $recent_appointments = $doctorController->getAppointments(10);
            $pending_appointments = $doctorController->getAppointments(null, 'pending');
            
        } catch (Exception $e) {
            error_log("Error in view_profile: " . $e->getMessage());
            redirectToLogin('Error loading profile data');
        }
        break;
}
?>