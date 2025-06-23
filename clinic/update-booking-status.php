<?php
session_start();

// Set content type for JSON response
header('Content-Type: application/json');

// Check if clinic is logged in
if (!isset($_SESSION['clinic_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$clinic_id = (int)$_SESSION['clinic_id'];

// Handle POST request to update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = trim($_POST['status']);
    
    // Validate status
    $valid_statuses = ['Pending', 'Confirmed', 'Sample Collected', 'In Progress', 'Completed', 'Cancelled', 'Upload Done'];
    
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status selected.']);
        exit();
    }
    
    // Verify booking belongs to this clinic
    $verify_sql = "SELECT id, status FROM lab_orders WHERE id = ? AND clinic_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $booking_id, $clinic_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found or access denied.']);
        exit();
    }
    
    $booking_data = $verify_result->fetch_assoc();
    $current_status = $booking_data['status'];
    
    // Check if status is actually changing
    if ($current_status === $new_status) {
        echo json_encode(['success' => true, 'message' => 'Status is already set to: ' . $new_status]);
        exit();
    }
    
    // Update the status
    $update_sql = "UPDATE lab_orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND clinic_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sii", $new_status, $booking_id, $clinic_id);
    
    if ($update_stmt->execute()) {
        // Check if any row was actually affected
        if ($update_stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Booking status updated successfully to: ' . $new_status,
                'new_status' => $new_status,
                'booking_id' => $booking_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made to the booking status.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update booking status. Database error occurred.']);
    }
    
    $conn->close();
    exit();
}

// Handle AJAX request to get booking for status update
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_booking_for_update' && isset($_GET['booking_id'])) {
    $booking_id = (int)$_GET['booking_id'];
    
    $sql = "SELECT id, booking_id, customer_name, status FROM lab_orders WHERE id = ? AND clinic_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking_id, $clinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($booking = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $booking]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
    }
    $conn->close();
    exit();
}

// Handle invalid requests
echo json_encode(['success' => false, 'message' => 'Invalid request method or missing parameters.']);
$conn->close();
exit();
?>