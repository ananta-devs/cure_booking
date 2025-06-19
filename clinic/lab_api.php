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
    die('<div class="error-message">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// Get clinic info
$clinic_id = (int)$_SESSION['clinic_id'];
$clinic_name = $_SESSION['clinic_name'] ?? 'Unknown Clinic';

// Determine action based on request parameters
$action = '';
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $action = 'update_status';
} else {
    $action = 'default';
}

// Handle different actions using switch statement
switch ($action) {
    case 'get_booking_details':
        handleGetBookingDetails($conn, $clinic_id);
        break;
        
    case 'get_booking_for_update':
        handleGetBookingForUpdate($conn, $clinic_id);
        break;
        
    case 'update_status':
        handleUpdateStatus($conn, $clinic_id);
        break;
        
    case 'default':
    default:
        handleDefaultView($conn, $clinic_id);
        break;
}

// Function to handle getting booking details
function handleGetBookingDetails($conn, $clinic_id) {
    if (!isset($_GET['booking_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Booking ID required']);
        exit();
    }
    
    $booking_id = (int)$_GET['booking_id'];
    
    // Get booking details with test items
    $detail_sql = "SELECT lo.*, 
                        GROUP_CONCAT(
                            CONCAT(loi.test_name, ' - ₹', loi.test_price) 
                            SEPARATOR '|'
                        ) as test_details
                FROM lab_orders lo 
                LEFT JOIN lab_order_items loi ON lo.id = loi.order_id 
                WHERE lo.id = ? AND lo.clinic_id = ?
                GROUP BY lo.id";
    
    try {
        $detail_stmt = $conn->prepare($detail_sql);
        $detail_stmt->bind_param("ii", $booking_id, $clinic_id);
        $detail_stmt->execute();
        $booking_details = $detail_stmt->get_result()->fetch_assoc();
        
        if ($booking_details) {
            header('Content-Type: application/json');
            echo json_encode($booking_details);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Booking not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit();
}

// Function to handle getting booking for status update
function handleGetBookingForUpdate($conn, $clinic_id) {
    if (!isset($_GET['booking_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Booking ID required']);
        exit();
    }
    
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

// Function to handle status update
function handleUpdateStatus($conn, $clinic_id) {
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

// Function to handle default view (original lab_api.php functionality)
function handleDefaultView($conn, $clinic_id) {
    // Pagination and filters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $date_filter = !empty($_GET['date_filter']) ? $_GET['date_filter'] : '';
    $status_filter = !empty($_GET['status_filter']) ? $_GET['status_filter'] : '';
    $search_query = !empty($_GET['search']) ? trim($_GET['search']) : '';

    // Build WHERE conditions
    $where_conditions = ["lo.clinic_id = ?"];
    $params = [$clinic_id];
    $types = "i";

    if ($date_filter) {
        $where_conditions[] = "DATE(lo.sample_collection_date) = ?";
        $params[] = $date_filter;
        $types .= "s";
    }

    if ($status_filter) {
        $where_conditions[] = "lo.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }

    if ($search_query) {
        $where_conditions[] = "(lo.customer_name LIKE ? OR lo.phone LIKE ? OR lo.booking_id LIKE ?)";
        $search_param = '%' . $search_query . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= "sss";
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Get total count for pagination
    $count_sql = "SELECT COUNT(DISTINCT lo.id) as total FROM lab_orders lo WHERE $where_clause";
    try {
        $count_stmt = $conn->prepare($count_sql);
        if (!empty($params)) {
            $count_stmt->bind_param($types, ...$params);
        }
        $count_stmt->execute();
        $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $limit);
    } catch (Exception $e) {
        $total_records = 0;
        $total_pages = 0;
    }

    // Main query with pagination
    $sql = "SELECT lo.*, 
                COUNT(loi.id) as test_count,
                GROUP_CONCAT(loi.test_name SEPARATOR ', ') as test_names
            FROM lab_orders lo 
            LEFT JOIN lab_order_items loi ON lo.id = loi.order_id 
            WHERE $where_clause
            GROUP BY lo.id 
            ORDER BY lo.sample_collection_date DESC, lo.created_at DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $bookings_result = $stmt->get_result();
    } catch (Exception $e) {
        die('<div class="error-message">Error fetching bookings: ' . htmlspecialchars($e->getMessage()) . '</div>');
    }

    // Get available statuses
    $status_sql = "SELECT DISTINCT status FROM lab_orders WHERE clinic_id = ? ORDER BY status";
    try {
        $status_stmt = $conn->prepare($status_sql);
        $status_stmt->bind_param("i", $clinic_id);
        $status_stmt->execute();
        $available_statuses = array_column($status_stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'status');
    } catch (Exception $e) {
        $available_statuses = ['Pending', 'Confirmed', 'Sample Collected', 'In Progress', 'Upload Done', 'Completed', 'Cancelled'];
    }

    // Make variables available globally for the view
    global $bookings_result, $total_records, $total_pages, $page, $available_statuses;
    global $date_filter, $status_filter, $search_query;
}

?>