<?php
// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['appointment_id']) || empty($input['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit();
}

$appointment_id = intval($input['appointment_id']);
$user_email = $_SESSION['user_email'] ?? '';

// Validate user email
if (empty($user_email)) {
    echo json_encode(['success' => false, 'message' => 'User email not found in session']);
    exit();
}

// First, check if the appointment exists and belongs to the user
$check_sql = "SELECT id, status, appointment_date, appointment_time FROM appointments WHERE id = ? AND booked_by_email = ?";
$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparing check statement']);
    exit();
}

$check_stmt->bind_param("is", $appointment_id, $user_email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found or you do not have permission to cancel it']);
    $check_stmt->close();
    $conn->close();
    exit();
}

$appointment = $result->fetch_assoc();

// Check if appointment is already cancelled
if (strtolower($appointment['status']) === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'Appointment is already cancelled']);
    $check_stmt->close();
    $conn->close();
    exit();
}

// Check if appointment is in the past
$appointment_datetime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
$appointment_timestamp = strtotime($appointment_datetime);
$current_timestamp = time();

if ($appointment_timestamp <= $current_timestamp) {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel past appointments']);
    $check_stmt->close();
    $conn->close();
    exit();
}

$check_stmt->close();

// Update appointment status to cancelled
$update_sql = "UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND booked_by_email = ?";
$update_stmt = $conn->prepare($update_sql);

if (!$update_stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparing update statement']);
    exit();
}

$update_stmt->bind_param("is", $appointment_id, $user_email);

if ($update_stmt->execute()) {
    if ($update_stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No appointment was updated. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating appointment: ' . $update_stmt->error]);
}

$update_stmt->close();
$conn->close();
?>