<?php
// unified_login_handler.php - Handles both doctor and clinic login authentication
session_start();

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

// Function to send JSON response
function sendResponse($success, $message, $redirect = null, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    
    if ($data) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Get and validate input data
$loginType = trim($_POST['login_type'] ?? '');
$email = trim($_POST['email'] ?? '');
$inputPassword = trim($_POST['password'] ?? '');

// Basic validation
if (empty($loginType)) {
    sendResponse(false, 'Login type is required');
}

if (empty($email) || empty($inputPassword)) {
    sendResponse(false, 'Email and password are required');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'Please enter a valid email address');
}

if (strlen($inputPassword) < 6) {
    sendResponse(false, 'Password must be at least 6 characters long');
}

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle different login types using switch case
    switch (strtolower($loginType)) {
        case 'doctor':
            handleDoctorLogin($pdo, $email, $inputPassword);
            break;
            
        case 'clinic':
            handleClinicLogin($pdo, $email, $inputPassword);
            break;
            
        default:
            sendResponse(false, 'Invalid login type. Please specify "doctor" or "clinic"');
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database error in login: " . $e->getMessage());
    sendResponse(false, 'Database connection failed. Please try again later.');
    
} catch (Exception $e) {
    error_log("General error in login: " . $e->getMessage());
    sendResponse(false, 'An unexpected error occurred. Please try again.');
}

// Function to handle doctor login
function handleDoctorLogin($pdo, $email, $inputPassword) {
    // Prepare and execute query to find doctor by email
    $stmt = $pdo->prepare("SELECT id, doc_name, doc_email, doc_pass, doc_specia, doc_img FROM doctor WHERE doc_email = :email LIMIT 1");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        sendResponse(false, 'No doctor account found with this email address');
    }
    
    // Try both hashed and plain text password verification
    $storedPassword = $doctor['doc_pass'];
    $passwordMatch = false;
    
    // First try password_verify (for hashed passwords)
    if (password_verify($inputPassword, $storedPassword)) {
        $passwordMatch = true;
    }
    // If that fails, try direct comparison (for plain text passwords)
    else if ($inputPassword === $storedPassword) {
        $passwordMatch = true;
    }
    
    if ($passwordMatch) {
        // Password is correct - create session
        $_SESSION['doctor_id'] = $doctor['id'];
        $_SESSION['doctor_name'] = $doctor['doc_name'];
        $_SESSION['doctor_email'] = $doctor['doc_email'];
        $_SESSION['doctor_specialty'] = $doctor['doc_specia'];
        $_SESSION['doctor_image'] = $doctor['doc_img'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['user_type'] = 'doctor';
        
        // Update last login (optional)
        try {
            $updateStmt = $pdo->prepare("UPDATE doctor SET last_login = NOW() WHERE id = :id");
            $updateStmt->bindParam(':id', $doctor['id'], PDO::PARAM_INT);
            $updateStmt->execute();
        } catch (Exception $e) {
            // Ignore if last_login column doesn't exist
            error_log("Doctor last login update failed: " . $e->getMessage());
        }
        
        sendResponse(true, "Welcome back, Dr. " . $doctor['doc_name'] . "!", "http://localhost/cure_booking/doctor/doctor_show.php", [
            'doctor_id' => $doctor['id'],
            'doctor_name' => $doctor['doc_name'],
            'user_type' => 'doctor'
        ]);
        
    } else {
        sendResponse(false, 'Invalid email or password');
    }
}

// Function to handle clinic login
function handleClinicLogin($pdo, $email, $inputPassword) {
    // Prepare and execute query to find clinic by email and check if active
    $stmt = $pdo->prepare("SELECT clinic_id, clinic_name, clinic_email, clinic_pass, location, contact_number, status FROM clinics WHERE clinic_email = :email LIMIT 1");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$clinic) {
        sendResponse(false, 'No clinic account found with this email address');
    }
    
    // Check if clinic is active
    if ($clinic['status'] !== 'active') {
        $statusMessage = '';
        switch ($clinic['status']) {
            case 'inactive':
                $statusMessage = 'Your clinic account is currently inactive. Please contact support.';
                break;
            case 'suspended':
                $statusMessage = 'Your clinic account has been suspended. Please contact support.';
                break;
            default:
                $statusMessage = 'Your clinic account is not available for login. Please contact support.';
        }
        sendResponse(false, $statusMessage);
    }
    
    // Try both hashed and plain text password verification
    $storedPassword = $clinic['clinic_pass'];
    $passwordMatch = false;
    
    // First try password_verify (for hashed passwords)
    if (password_verify($inputPassword, $storedPassword)) {
        $passwordMatch = true;
    }
    // If that fails, try direct comparison (for plain text passwords)
    else if ($inputPassword === $storedPassword) {
        $passwordMatch = true;
    }
    
    if ($passwordMatch) {
        // Clear any existing sessions to prevent conflicts
        session_unset();
        session_regenerate_id(true);
        
        // Password is correct - create session
        $_SESSION['clinic_id'] = $clinic['clinic_id'];
        $_SESSION['clinic_name'] = $clinic['clinic_name'];
        $_SESSION['clinic_email'] = $clinic['clinic_email'];
        $_SESSION['clinic_location'] = $clinic['location'];
        $_SESSION['contact_number'] = $clinic['contact_number'];
        $_SESSION['clinic_status'] = $clinic['status'];
        $_SESSION['logged_in'] = true;
        $_SESSION['clinic_logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['clinic_login_time'] = time();
        $_SESSION['user_type'] = 'clinic';
        
        // Update last login timestamp (add column if it doesn't exist)
        try {
            $updateStmt = $pdo->prepare("UPDATE clinics SET updated_at = NOW() WHERE clinic_id = :id");
            $updateStmt->bindParam(':id', $clinic['clinic_id'], PDO::PARAM_INT);
            $updateStmt->execute();
        } catch (Exception $e) {
            error_log("Clinic last login update failed: " . $e->getMessage());
        }
        
        sendResponse(true, "Welcome back, " . $clinic['clinic_name'] . "!", "http://localhost/cure_booking/clinic/home.php", [
            'clinic_id' => $clinic['clinic_id'],
            'clinic_name' => $clinic['clinic_name'],
            'user_type' => 'clinic',
            'status' => $clinic['status']
        ]);
        
    } else {
        sendResponse(false, 'Invalid email or password');
    }
}
?>