<?php
// add_doctor_handler.php - Handles adding new doctors by logged-in clinics
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Check if user is logged in as clinic
if (!isset($_SESSION['clinic_logged_in']) || $_SESSION['clinic_logged_in'] !== true || !isset($_SESSION['clinic_id'])) {
    sendResponse(false, 'You must be logged in as a clinic to add doctors.');
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed');
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get clinic ID from session
    $clinic_id = $_SESSION['clinic_id'];
    
    // Validate and sanitize input data
    $doc_name = trim($_POST['doc_name'] ?? '');
    $doc_specia = trim($_POST['doc_specia'] ?? '');
    $doc_email = trim($_POST['doc_email'] ?? '');
    $fees = floatval($_POST['fees'] ?? 0);
    $gender = trim($_POST['gender'] ?? '');
    $experience = intval($_POST['experience'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $doc_pass = trim($_POST['doc_pass'] ?? '');
    
    // Get availability schedule from POST data
    $availability_schedule = $_POST['availability_schedule'] ?? '';
    
    // Validation
    if (empty($doc_name)) {
        sendResponse(false, 'Doctor name is required.');
    }
    
    if (empty($doc_specia)) {
        sendResponse(false, 'Specialization is required.');
    }
    
    if ($fees <= 0) {
        sendResponse(false, 'Valid consultation fees are required.');
    }
    
    if (empty($doc_pass)) {
        sendResponse(false, 'Password is required.');
    }
    
    if (strlen($doc_pass) < 6) {
        sendResponse(false, 'Password must be at least 6 characters long.');
    }
    
    // Validate email if provided
    if (!empty($doc_email) && !filter_var($doc_email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Please enter a valid email address.');
    }
    
    // Check if email already exists (if provided)
    if (!empty($doc_email)) {
        $emailCheckStmt = $pdo->prepare("SELECT doc_id FROM doctor WHERE doc_email = :email");
        $emailCheckStmt->bindParam(':email', $doc_email);
        $emailCheckStmt->execute();
        
        if ($emailCheckStmt->rowCount() > 0) {
            sendResponse(false, 'A doctor with this email already exists.');
        }
    }
    
    // Handle file upload
    $doc_img = null;
    if (isset($_FILES['doc_img']) && $_FILES['doc_img']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/doctors/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['doc_img']['name'], PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            sendResponse(false, 'Only JPG, JPEG, PNG, and GIF files are allowed.');
        }
        
        // Check file size (max 5MB)
        if ($_FILES['doc_img']['size'] > 5 * 1024 * 1024) {
            sendResponse(false, 'File size must be less than 5MB.');
        }
        
        // Generate unique filename
        $doc_img = uniqid() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $doc_img;
        
        if (!move_uploaded_file($_FILES['doc_img']['tmp_name'], $uploadPath)) {
            sendResponse(false, 'Failed to upload image.');
        }
    }
    
    // Hash the password
    $hashed_password = password_hash($doc_pass, PASSWORD_DEFAULT);
    
    // Prepare availability schedule - use provided schedule or default
    if (empty($availability_schedule)) {
        // Create default availability schedule (empty schedule - can be updated later)
        $default_schedule = json_encode([
            'monday' => [
                '11:00-13:00' => false,
                '14:00-16:00' => false,
                '17:00-19:00' => false
            ],
            'tuesday' => [
                '11:00-13:00' => false,
                '14:00-16:00' => false,
                '17:00-19:00' => false
            ],
            'wednesday' => [
                '11:00-13:00' => false,
                '14:00-16:00' => false,
                '17:00-19:00' => false
            ],
            'thursday' => [
                '11:00-13:00' => false,
                '14:00-16:00' => false,
                '17:00-19:00' => false
            ],
            'friday' => [
                '11:00-13:00' => false,
                '14:00-16:00' => false,
                '17:00-19:00' => false
            ],
            'saturday' => [
                '11:00-13:00' => false,
                '14:00-16:00' => false,
                '17:00-19:00' => false
            ],
            'sunday' => [
                '11:00-13:00' => false,
                '14:00-16:00' => false,
                '17:00-19:00' => false
            ]
        ]);
        $availability_schedule = $default_schedule;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Insert doctor into doctor table (WITHOUT fees - fees go to assignments table)
        $insertDoctorStmt = $pdo->prepare("
            INSERT INTO doctor (doc_name, doc_specia, doc_email, doc_img, gender, experience, location, education, bio, doc_pass) 
            VALUES (:doc_name, :doc_specia, :doc_email, :doc_img, :gender, :experience, :location, :education, :bio, :doc_pass)
        ");
        
        $insertDoctorStmt->bindParam(':doc_name', $doc_name);
        $insertDoctorStmt->bindParam(':doc_specia', $doc_specia);
        $insertDoctorStmt->bindParam(':doc_email', $doc_email);
        $insertDoctorStmt->bindParam(':doc_img', $doc_img);
        $insertDoctorStmt->bindParam(':gender', $gender);
        $insertDoctorStmt->bindParam(':experience', $experience);
        $insertDoctorStmt->bindParam(':location', $location);
        $insertDoctorStmt->bindParam(':education', $education);
        $insertDoctorStmt->bindParam(':bio', $bio);
        $insertDoctorStmt->bindParam(':doc_pass', $hashed_password);
        
        $insertDoctorStmt->execute();
        
        // Get the inserted doctor ID
        $doctor_id = $pdo->lastInsertId();
        
        // Insert doctor-clinic assignment WITH fees and availability schedule
        $insertAssignmentStmt = $pdo->prepare("
            INSERT INTO doctor_clinic_assignments (doctor_id, clinic_id, fees, availability_schedule) 
            VALUES (:doctor_id, :clinic_id, :fees, :availability_schedule)
        ");
        
        $insertAssignmentStmt->bindParam(':doctor_id', $doctor_id);
        $insertAssignmentStmt->bindParam(':clinic_id', $clinic_id);
        $insertAssignmentStmt->bindParam(':fees', $fees);
        $insertAssignmentStmt->bindParam(':availability_schedule', $availability_schedule);
        
        $insertAssignmentStmt->execute();
        
        // Commit transaction
        $pdo->commit();
        
        sendResponse(true, "Doctor '{$doc_name}' has been successfully added to your clinic with consultation fees of $fees!", [
            'doctor_id' => $doctor_id,
            'clinic_id' => $clinic_id,
            'fees' => $fees
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        
        // Delete uploaded file if it exists
        if ($doc_img && file_exists($uploadDir . $doc_img)) {
            unlink($uploadDir . $doc_img);
        }
        
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error in add doctor: " . $e->getMessage());
    sendResponse(false, 'Database error occurred. Please try again.');
    
} catch (Exception $e) {
    error_log("General error in add doctor: " . $e->getMessage());
    sendResponse(false, 'An unexpected error occurred. Please try again.');
}
?>