<?php
session_start();

// Check if clinic is logged in
if (!isset($_SESSION['clinic_id'])) {
    header("Location: login.php");
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
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

$clinic_id = (int)$_SESSION['clinic_id'];

// Handle POST request to update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = trim($_POST['status']);
    
    // Validate status
    $valid_statuses = ['Pending', 'Confirmed', 'Sample Collected', 'In Progress', 'Completed', 'Cancelled', 'Upload Done'];
    
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error'] = "Invalid status selected.";
        header("Location: lab-bookings.php");
        exit();
    }
    
    // Verify booking belongs to this clinic
    $verify_sql = "SELECT id FROM lab_orders WHERE id = ? AND clinic_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $booking_id, $clinic_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $_SESSION['error'] = "Booking not found or access denied.";
        header("Location: lab-bookings.php");
        exit();
    }
    
    // Update the status
    $update_sql = "UPDATE lab_orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND clinic_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sii", $new_status, $booking_id, $clinic_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Booking status updated successfully to: " . $new_status;
        
        // If status is 'Completed', redirect to upload report page
        if ($new_status === 'Completed') {
            header("Location: upload-report.php?booking_id=" . $booking_id);
            exit();
        }
    } else {
        $_SESSION['error'] = "Failed to update booking status. Please try again.";
    }
    
    header("Location: lab-bookings.php");
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
        header('Content-Type: application/json');
        echo json_encode($booking);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
    }
    exit();
}

// If direct access, redirect to lab bookings
header("Location: lab-bookings.php");
exit();
?>