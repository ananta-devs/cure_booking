<?php
// Start session to get logged-in user info
session_start();

// Prevent PHP notices and warnings from breaking the JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set the content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to book appointments', 'redirect_to_login' => true]);
    exit;
}

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data with enhanced validation
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
    $name = isset($_POST['name']) ? trim($conn->real_escape_string($_POST['name'])) : '';
    $phone = isset($_POST['phone']) ? trim($conn->real_escape_string($_POST['phone'])) : '';
    $email = isset($_POST['email']) ? trim($conn->real_escape_string($_POST['email'])) : '';
    $date = isset($_POST['date']) ? $conn->real_escape_string($_POST['date']) : '';
    $time = isset($_POST['time']) ? $conn->real_escape_string($_POST['time']) : '';

    // Enhanced validation
    if (empty($doctor_id) || empty($name) || empty($phone) || empty($email) || empty($date) || empty($time)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }

    // Validate phone number (basic validation)
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
        exit;
    }

    // Validate appointment date is not in the past
    $appointment_date = new DateTime($date);
    $today = new DateTime();
    $today->setTime(0, 0, 0); // Set to beginning of day for comparison
    
    if ($appointment_date < $today) {
        echo json_encode(['success' => false, 'message' => 'Appointment date cannot be in the past']);
        exit;
    }

    // Validate appointment date is not more than 3 months in advance
    $max_date = new DateTime();
    $max_date->add(new DateInterval('P3M')); // Add 3 months
    
    if ($appointment_date > $max_date) {
        echo json_encode(['success' => false, 'message' => 'Appointments can only be booked up to 3 months in advance']);
        exit;
    }

    // Get logged-in user information with fallback methods
    $booked_by_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['userId'] ?? 0;
    $booked_by_email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? $_SESSION['userEmail'] ?? '';
    $booked_by_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? $_SESSION['userName'] ?? '';
    
    // If we still don't have user data, try to get it from the database
    if (empty($booked_by_name) || empty($booked_by_email) || $booked_by_user_id <= 0) {
        if ($booked_by_user_id > 0) {
            $user_query = "SELECT id, name, email FROM users WHERE id = ?";
            $user_stmt = $conn->prepare($user_query);
            if ($user_stmt) {
                $user_stmt->bind_param("i", $booked_by_user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                if ($user_result->num_rows > 0) {
                    $user_data = $user_result->fetch_assoc();
                    $booked_by_name = $booked_by_name ?: $user_data['name'];
                    $booked_by_email = $booked_by_email ?: $user_data['email'];
                    $booked_by_user_id = $user_data['id'];
                }
                $user_stmt->close();
            }
        } elseif (!empty($booked_by_email)) {
            $user_query = "SELECT id, name, email FROM users WHERE email = ?";
            $user_stmt = $conn->prepare($user_query);
            if ($user_stmt) {
                $user_stmt->bind_param("s", $booked_by_email);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                if ($user_result->num_rows > 0) {
                    $user_data = $user_result->fetch_assoc();
                    $booked_by_name = $booked_by_name ?: $user_data['name'];
                    $booked_by_user_id = $user_data['id'];
                }
                $user_stmt->close();
            }
        }
    }
    
    // Final validation - user must be properly identified
    if ($booked_by_user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unable to identify user. Please login again.', 'redirect_to_login' => true]);
        exit;
    }

    try {
        // Check if doctor exists and get details
        $doctor_query = "SELECT doc_name, doc_specia FROM doctor WHERE id = ?";
        $stmt_doctor = $conn->prepare($doctor_query);
        
        if (!$stmt_doctor) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt_doctor->bind_param("i", $doctor_id);
        $stmt_doctor->execute();
        $doctor_result = $stmt_doctor->get_result();
        
        if ($doctor_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
            exit;
        }
        
        $doctor_data = $doctor_result->fetch_assoc();
        $doctor_name = $doctor_data['doc_name'];
        $doctor_specialization = $doctor_data['doc_specia'];
        $stmt_doctor->close();

        // Check for duplicate bookings (same doctor, date, time)
        $duplicate_check = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
        $stmt_duplicate = $conn->prepare($duplicate_check);
        if ($stmt_duplicate) {
            $stmt_duplicate->bind_param("iss", $doctor_id, $date, $time);
            $stmt_duplicate->execute();
            $duplicate_result = $stmt_duplicate->get_result();
            
            if ($duplicate_result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please choose a different time.']);
                $stmt_duplicate->close();
                exit;
            }
            $stmt_duplicate->close();
        }

        // Check rate limiting - prevent spam bookings (max 3 bookings per hour per user)
        $rate_limit_check = "SELECT COUNT(*) as booking_count FROM appointments WHERE booked_by_user_id = ? AND booking_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt_rate = $conn->prepare($rate_limit_check);
        if ($stmt_rate) {
            $stmt_rate->bind_param("i", $booked_by_user_id);
            $stmt_rate->execute();
            $rate_result = $stmt_rate->get_result();
            $rate_data = $rate_result->fetch_assoc();
            
            if ($rate_data['booking_count'] >= 3) {
                echo json_encode(['success' => false, 'message' => 'Too many bookings in the last hour. Please wait before booking again.']);
                $stmt_rate->close();
                exit;
            }
            $stmt_rate->close();
        }

        // Convert time to proper format for database
        $time_parts = explode('-', $time);
        $start_time = $time_parts[0] . ':00';

        // Insert appointment data with all necessary information
        $sql = "INSERT INTO appointments (doctor_id, doctor_name, doctor_specialization, patient_name, patient_phone, patient_email, appointment_date, appointment_time, booking_date, booked_by_user_id, booked_by_email, booked_by_name, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, 'confirmed')";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("issssssiiss", $doctor_id, $doctor_name, $doctor_specialization, $name, $phone, $email, $date, $start_time, $booked_by_user_id, $booked_by_email, $booked_by_name);
        
        if ($stmt->execute()) {
            $appointment_id = $conn->insert_id;
            
            // Successfully inserted
            echo json_encode([
                'success' => true, 
                'message' => 'Appointment booked successfully!',
                'appointment_id' => $appointment_id,
                'patient_name' => $name,
                'doctor_name' => $doctor_name,
                'appointment_date' => date('M d, Y', strtotime($date)),
                'appointment_time' => $time,
                'booked_by' => $booked_by_name
            ]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>