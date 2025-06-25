<?php
// api.php
session_start(); // Start session to access logged-in user data

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = '127.0.0.1';
$dbname = 'cure_booking';
$username = 'root'; // Adjust as needed
$password = '';     // Adjust as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getDoctorProfile':
        getDoctorProfile($pdo);
        break;
    case 'getLoggedInDoctor':
        getLoggedInDoctor();
        break;
    case 'changePassword':
        changePassword($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getLoggedInDoctor() {
    // Check if doctor is logged in via session
    if (isset($_SESSION['doctor_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && $_SESSION['user_type'] === 'doctor') {
        echo json_encode([
            'success' => true,
            'doctor_id' => $_SESSION['doctor_id'],
            'doctor_name' => $_SESSION['doctor_name'] ?? '',
            'doctor_email' => $_SESSION['doctor_email'] ?? '',
            'doctor_specialty' => $_SESSION['doctor_specialty'] ?? '',
            'message' => 'Doctor session found'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'No doctor logged in or session expired'
        ]);
    }
}

function changePassword($pdo) {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    // Check if doctor is logged in
    if (!isset($_SESSION['doctor_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'doctor') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        return;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        return;
    }

    $currentPassword = trim($input['currentPassword'] ?? '');
    $newPassword = trim($input['newPassword'] ?? '');

    // Validate input
    if (empty($currentPassword) || empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Current password and new password are required']);
        return;
    }

    // Validate new password strength
    if (!isValidPassword($newPassword)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'New password must be at least 8 characters long and contain uppercase, lowercase, number, and special character'
        ]);
        return;
    }

    // Check if new password is different from current
    if ($currentPassword === $newPassword) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'New password must be different from current password'
        ]);
        return;
    }

    try {
        $doctorId = $_SESSION['doctor_id'];

        // Get current password from database
        $stmt = $pdo->prepare("SELECT doc_pass FROM doctor WHERE doc_id = ?");
        $stmt->execute([$doctorId]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
            return;
        }

        // Check if current password is already hashed or plain text
        $isCurrentPasswordCorrect = false;
        
        // First try password_verify (for hashed passwords)
        if (password_verify($currentPassword, $doctor['doc_pass'])) {
            $isCurrentPasswordCorrect = true;
        } 
        // Fallback: check if it's plain text (for legacy passwords like in your data)
        elseif ($currentPassword === $doctor['doc_pass']) {
            $isCurrentPasswordCorrect = true;
        }

        if (!$isCurrentPasswordCorrect) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => 'invalid_current_password',
                'message' => 'Current password is incorrect'
            ]);
            return;
        }

        // Hash new password
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password in database (removed updated_at since column doesn't exist)
        $updateStmt = $pdo->prepare("UPDATE doctor SET doc_pass = ? WHERE doc_id = ?");
        $updateResult = $updateStmt->execute([$hashedNewPassword, $doctorId]);

        if ($updateResult) {
            // Optional: Create a simple log table if you want to track password changes
            // You can run this SQL to create the table:
            /*
            CREATE TABLE IF NOT EXISTS password_change_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doctor_id INT NOT NULL,
                changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                FOREIGN KEY (doctor_id) REFERENCES doctor(doc_id)
            );
            */
            
            // Try to log the password change (ignore if table doesn't exist)
            try {
                $logStmt = $pdo->prepare("
                    INSERT INTO password_change_log (doctor_id, changed_at, ip_address) 
                    VALUES (?, NOW(), ?)
                ");
                
                // Get client IP address
                $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $logStmt->execute([$doctorId, $ipAddress]);
            } catch (PDOException $e) {
                // Silently ignore logging errors - table might not exist
                error_log("Password change logging failed: " . $e->getMessage());
            }

            echo json_encode([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update password. Please try again.'
            ]);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again later.'
        ]);
        error_log("Password change error for doctor ID $doctorId: " . $e->getMessage());
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An unexpected error occurred. Please try again later.'
        ]);
        error_log("Unexpected error in changePassword: " . $e->getMessage());
    }
}

function isValidPassword($password) {
    // Check password requirements:
    // - At least 8 characters
    // - At least one uppercase letter
    // - At least one lowercase letter  
    // - At least one number
    // - At least one special character
    
    if (strlen($password) < 8) {
        return false;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/\d/', $password)) {
        return false;
    }
    
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return false;
    }
    
    return true;
}

function getDoctorProfile($pdo) {
    $doctorId = $_GET['doctor_id'] ?? null;
    
    // If no doctor_id provided, try to get from session
    if (!$doctorId && isset($_SESSION['doctor_id'])) {
        $doctorId = $_SESSION['doctor_id'];
    }
    
    if (!$doctorId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Doctor ID is required']);
        return;
    }

    // Additional security: If there's a session, make sure the requested doctor matches the logged-in doctor
    if (isset($_SESSION['doctor_id']) && $_SESSION['doctor_id'] != $doctorId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied: Cannot view other doctor profiles']);
        return;
    }

    try {
        // Get doctor information
        $doctorStmt = $pdo->prepare("
            SELECT doc_id, doc_name, doc_specia, doc_email, doc_img, 
                   gender, experience, fees, location, education, bio
            FROM doctor 
            WHERE doc_id = ?
        ");
        $doctorStmt->execute([$doctorId]);
        $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
            return;
        }

        // Get clinic assignments and availability
        $clinicsStmt = $pdo->prepare("
            SELECT 
                c.clinic_id,
                c.clinic_name,
                c.clinic_email,
                c.contact_number,
                c.location,
                c.available_timing,
                c.about as clinic_about,
                dca.availability_schedule
            FROM doctor_clinic_assignments dca
            JOIN clinics c ON dca.clinic_id = c.clinic_id
            WHERE dca.doctor_id = ? AND c.status = 'active'
            ORDER BY c.clinic_name
        ");
        $clinicsStmt->execute([$doctorId]);
        $clinics = $clinicsStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'doctor' => $doctor,
            'clinics' => $clinics,
            'session_doctor_id' => $_SESSION['doctor_id'] ?? null // For debugging
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>