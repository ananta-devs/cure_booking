<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if clinic is logged in
if (!isset($_SESSION['clinic_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
include_once './include/database_connection.php';

$clinic_id = (int)$_SESSION['clinic_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_bookings':
            getBookings($conn, $clinic_id);
            break;
            
        case 'get_booking_details':
            getBookingDetails($conn, $_GET['booking_id'] ?? '');
            break;
            
        case 'update_status':
            updateBookingStatus($conn, $_POST['booking_id'] ?? '', $_POST['status'] ?? '');
            break;
            
        case 'upload_report':
            uploadReport($conn, $_POST['booking_id'] ?? '');
            break;
            
        case 'delete_booking':
            deleteBooking($conn, $_POST['booking_id'] ?? '');
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getBookings($conn, $clinic_id) {
    // First check if tables exist and have data
    $check_sql = "SELECT COUNT(*) as count FROM lab_orders WHERE clinic_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
        return;
    }
    
    $check_stmt->bind_param("i", $clinic_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count_data = $check_result->fetch_assoc();
    
    if ($count_data['count'] == 0) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No bookings found for this clinic']);
        return;
    }
    
    $sql = "SELECT 
                lo.id,
                lo.booking_id,
                lo.customer_name,
                lo.phone,
                lo.email,
                lo.address,
                lo.sample_collection_date,
                lo.time_slot,
                lo.total_amount,
                lo.status,
                lo.created_at,
                lo.updated_at,
                lo.report_file,
                lo.report_uploaded_at,
                GROUP_CONCAT(DISTINCT loi.test_name ORDER BY loi.test_name SEPARATOR ', ') as tests,
                COUNT(DISTINCT loi.id) as test_count
            FROM lab_orders lo
            LEFT JOIN lab_order_items loi ON lo.id = loi.order_id
            WHERE lo.clinic_id = ?
            GROUP BY lo.id, lo.booking_id, lo.customer_name, lo.phone, lo.email, lo.address, 
                     lo.sample_collection_date, lo.time_slot, lo.total_amount, lo.status, 
                     lo.created_at, lo.updated_at, lo.report_file, lo.report_uploaded_at
            ORDER BY lo.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("i", $clinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure test_count is at least 0
        if ($row['test_count'] === null) {
            $row['test_count'] = 0;
        }
        
        // Format dates safely
        $row['formatted_collection_date'] = $row['sample_collection_date'] ? 
            date('M d, Y', strtotime($row['sample_collection_date'])) : 'N/A';
        $row['formatted_created_date'] = $row['created_at'] ? 
            date('M d, Y H:i', strtotime($row['created_at'])) : 'N/A';
        $row['formatted_updated_date'] = $row['updated_at'] ? 
            date('M d, Y H:i', strtotime($row['updated_at'])) : 'N/A';
        
        // Status badge class
        $row['status_class'] = getStatusClass($row['status']);
        
        // Has report
        $row['has_report'] = !empty($row['report_file']);
        
        // Ensure numeric fields are numbers
        $row['total_amount'] = (float)$row['total_amount'];
        $row['test_count'] = (int)$row['test_count'];
        
        $bookings[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $bookings, 'count' => count($bookings)]);
}

function getBookingDetails($conn, $booking_id) {
    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
        return;
    }
    
    // Get booking details with tests
    $sql = "SELECT 
                lo.*,
                GROUP_CONCAT(
                    CONCAT(loi.test_name, ' - ₹', loi.test_price, ' (', COALESCE(loi.sample_type, 'N/A'), ')')
                    ORDER BY loi.test_name SEPARATOR '|'
                ) as test_details
            FROM lab_orders lo
            LEFT JOIN lab_order_items loi ON lo.id = loi.order_id
            WHERE lo.booking_id = ?
            GROUP BY lo.id";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Format test details
        if ($row['test_details']) {
            $tests = explode('|', $row['test_details']);
            $row['tests'] = $tests;
        } else {
            $row['tests'] = [];
        }
        
        // Format dates safely
        $row['formatted_collection_date'] = $row['sample_collection_date'] ? 
            date('M d, Y', strtotime($row['sample_collection_date'])) : 'N/A';
        $row['formatted_created_date'] = $row['created_at'] ? 
            date('M d, Y H:i A', strtotime($row['created_at'])) : 'N/A';
        $row['formatted_updated_date'] = $row['updated_at'] ? 
            date('M d, Y H:i A', strtotime($row['updated_at'])) : 'N/A';
        
        // Status info
        $row['status_class'] = getStatusClass($row['status']);
        $row['has_report'] = !empty($row['report_file']);
        
        if ($row['report_uploaded_at']) {
            $row['formatted_report_date'] = date('M d, Y H:i A', strtotime($row['report_uploaded_at']));
        }
        
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
    }
}

function updateBookingStatus($conn, $booking_id, $status) {
    if (empty($booking_id) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Booking ID and status are required']);
        return;
    }
    
    $valid_statuses = ['Pending', 'Confirmed', 'Sample Collected', 'In Progress', 'Upload Done', 'Completed', 'Cancelled'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $sql = "UPDATE lab_orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("ss", $status, $booking_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking not found or no changes made']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $stmt->error]);
    }
}

// function uploadReport($conn, $booking_id) {
//     if (empty($booking_id)) {
//         echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
//         return;
//     }
    
//     if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
//         echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
//         return;
//     }
    
//     $file = $_FILES['report_file'];
    
//     // Validate file type
//     $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 
//                      'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
//     if (!in_array($file['type'], $allowed_types)) {
//         echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, PNG, DOC, DOCX allowed']);
//         return;
//     }
    
//     // Validate file size (10MB max)
//     if ($file['size'] > 10 * 1024 * 1024) {
//         echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 10MB allowed']);
//         return;
//     }
    
//     // Create upload directory if it doesn't exist
//     $upload_dir = '..adminhub/lab-bookings/uploads/lab_reports/';
//     if (!file_exists($upload_dir)) {
//         mkdir($upload_dir, 0755, true);
//     }
    
//     // Generate unique filename
//     $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
//     $filename = $booking_id . '_' . time() . '.' . $file_extension;
//     $filepath = $upload_dir . $filename;
    
//     // Check if booking exists and get current report file
//     $check_sql = "SELECT report_file FROM lab_orders WHERE booking_id = ?";
//     $check_stmt = $conn->prepare($check_sql);
//     if (!$check_stmt) {
//         echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
//         return;
//     }
    
//     $check_stmt->bind_param("s", $booking_id);
//     $check_stmt->execute();
//     $check_result = $check_stmt->get_result();
    
//     if ($check_result->num_rows === 0) {
//         echo json_encode(['success' => false, 'message' => 'Booking not found']);
//         return;
//     }
    
//     $current_data = $check_result->fetch_assoc();
//     $old_file = $current_data['report_file'];
    
//     // Move uploaded file
//     if (move_uploaded_file($file['tmp_name'], $filepath)) {
//         // Update database
//         $sql = "UPDATE lab_orders SET 
//                     report_file = ?, 
//                     report_uploaded_at = CURRENT_TIMESTAMP,
//                     status = CASE WHEN status != 'Completed' THEN 'Upload Done' ELSE status END,
//                     updated_at = CURRENT_TIMESTAMP 
//                 WHERE booking_id = ?";
        
//         $stmt = $conn->prepare($sql);
//         if (!$stmt) {
//             unlink($filepath);
//             echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
//             return;
//         }
        
//         $stmt->bind_param("ss", $filename, $booking_id);
        
//         if ($stmt->execute()) {
//             // Delete old file if exists
//             if ($old_file && file_exists($upload_dir . $old_file)) {
//                 unlink($upload_dir . $old_file);
//             }
            
//             echo json_encode([
//                 'success' => true, 
//                 'message' => 'Report uploaded successfully',
//                 'filename' => $filename
//             ]);
//         } else {
//             // Delete uploaded file if database update failed
//             unlink($filepath);
//             echo json_encode(['success' => false, 'message' => 'Failed to update database: ' . $stmt->error]);
//         }
//     } else {
//         echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
//     }
// }
function uploadReport($conn, $booking_id) {
    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
        return;
    }
    
    // Debug: Log the FILES array
    error_log("FILES array: " . print_r($_FILES, true));
    
    if (!isset($_FILES['report_file'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded - report_file not found in request']);
        return;
    }
    
    if ($_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File size exceeds server limit',
            UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $error_code = $_FILES['report_file']['error'];
        $error_message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : 'Unknown upload error';
        
        echo json_encode(['success' => false, 'message' => 'Upload error: ' . $error_message]);
        return;
    }
    
    $file = $_FILES['report_file'];
    
    // Get file info safely
    $file_mime = mime_content_type($file['tmp_name']);
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type by both MIME and extension
    $allowed_mimes = [
        'application/pdf', 
        'image/jpeg', 
        'image/jpg', 
        'image/png',
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    
    if (!in_array($file_mime, $allowed_mimes) && !in_array($file_extension, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, PNG, DOC, DOCX allowed. Detected: ' . $file_mime]);
        return;
    }
    
    // Validate file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 10MB allowed']);
        return;
    }
    
    // Define base path relative to the clinic folder
    define('BASE_PATH', dirname(__DIR__, 2) . '/');
    $upload_dir = BASE_PATH . 'adminhub/lab-bookings/uploads/lab_reports/';

    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            return;
        }
    }
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        echo json_encode(['success' => false, 'message' => 'Upload directory is not writable']);
        return;
    }
    
    // Generate unique filename with proper sanitization
    $safe_booking_id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $booking_id);
    $filename = $safe_booking_id . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    // Check if booking exists and get current report file
    $check_sql = "SELECT report_file FROM lab_orders WHERE booking_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
        return;
    }
    
    $check_stmt->bind_param("s", $booking_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        return;
    }
    
    $current_data = $check_result->fetch_assoc();
    $old_file = $current_data['report_file'];
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        $sql = "UPDATE lab_orders SET 
                    report_file = ?, 
                    report_uploaded_at = CURRENT_TIMESTAMP,
                    status = CASE WHEN status != 'Completed' THEN 'Upload Done' ELSE status END,
                    updated_at = CURRENT_TIMESTAMP 
                WHERE booking_id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // Remove uploaded file if database prepare fails
            unlink($filepath);
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            return;
        }
        
        $stmt->bind_param("ss", $filename, $booking_id);
        
        if ($stmt->execute()) {
            // Delete old file if exists
            if ($old_file && file_exists($upload_dir . $old_file)) {
                unlink($upload_dir . $old_file);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Report uploaded successfully',
                'filename' => $filename,
                'filepath' => $filepath
            ]);
        } else {
            // Delete uploaded file if database update failed
            unlink($filepath);
            echo json_encode(['success' => false, 'message' => 'Failed to update database: ' . $stmt->error]);
        }
    } else {
        // Get more specific error information
        $upload_error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'Failed to upload file. Error: ' . ($upload_error['message'] ?? 'Unknown error')]);
    }
}

function deleteBooking($conn, $booking_id) {
    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Get order ID and report file
        $get_sql = "SELECT id, report_file FROM lab_orders WHERE booking_id = ?";
        $get_stmt = $conn->prepare($get_sql);
        if (!$get_stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $get_stmt->bind_param("s", $booking_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Booking not found');
        }
        
        $order_data = $result->fetch_assoc();
        $order_id = $order_data['id'];
        $report_file = $order_data['report_file'];
        
        // Delete order items first
        $delete_items_sql = "DELETE FROM lab_order_items WHERE order_id = ?";
        $delete_items_stmt = $conn->prepare($delete_items_sql);
        if (!$delete_items_stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $delete_items_stmt->bind_param("i", $order_id);
        $delete_items_stmt->execute();
        
        // Delete order
        $delete_order_sql = "DELETE FROM lab_orders WHERE id = ?";
        $delete_order_stmt = $conn->prepare($delete_order_sql);
        if (!$delete_order_stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $delete_order_stmt->bind_param("i", $order_id);
        $delete_order_stmt->execute();
        
        $conn->commit();
        
        // Delete report file if exists
        if ($report_file && file_exists('./uploads/lab_reports/' . $report_file)) {
            unlink('./uploads/lab_reports/' . $report_file);
        }
        
        echo json_encode(['success' => true, 'message' => 'Booking deleted successfully']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete booking: ' . $e->getMessage()]);
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'Pending':
            return 'status-pending';
        case 'Confirmed':
            return 'status-confirmed';
        case 'Sample Collected':
            return 'status-collected';
        case 'In Progress':
            return 'status-progress';
        case 'Upload Done':
            return 'status-uploaded';
        case 'Completed':
            return 'status-completed';
        case 'Cancelled':
            return 'status-cancelled';
        default:
            return 'status-pending';
    }
}

if (isset($conn)) {
    $conn->close();
}
?>