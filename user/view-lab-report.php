<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("Invalid request: Order ID is required");
}

$order_id = intval($_GET['order_id']);
$user_email = $_SESSION['email'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? 'view'; // 'view' or 'download'

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch lab order with report file - ensure user has access to this order
$stmt = $pdo->prepare("
    SELECT 
        lo.id,
        lo.booking_id,
        lo.customer_name,
        lo.email,
        lo.status,
        lo.report_file,
        lo.report_uploaded_at,
        lo.booked_by_user_id
    FROM lab_orders lo
    WHERE lo.id = ? 
    AND ((lo.booked_by_user_id = ? AND lo.booked_by_user_id IS NOT NULL) 
         OR lo.email = ?)
");

$stmt->execute([$order_id, $user_id, $user_email]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if order exists and user has access
if (!$order) {
    die("Order not found or you don't have permission to access this report");
}

// Check if report is available
if (!in_array($order['status'], ['Upload Done'])) {
    die("Lab report is not yet available. Current status: " . $order['status']);
}

// Check if report file exists
if (empty($order['report_file'])) {
    die("No report file found for this order");
}

// Define the upload directory where reports are stored
$upload_dir = "../../adminhub/lab-bookings/uploads/reports/"; // Adjust this path according to your server setup
$file_path = $upload_dir . $order['report_file'];

// Check if file exists on server
if (!file_exists($file_path)) {
    die("Report file not found on server. Please contact support.");
}

// Get file info
$file_extension = strtolower(pathinfo($order['report_file'], PATHINFO_EXTENSION));
$file_size = filesize($file_path);

// Generate a user-friendly filename
$download_filename = "Lab_Report_" . $order['booking_id'] . "_" . $order['customer_name'] . "." . $file_extension;
$download_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $download_filename);

// If action is download, force download
if ($action === 'download') {
    $content_type = 'application/octet-stream';
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . $file_size);
    header('Content-Disposition: attachment; filename="' . $download_filename . '"');
    header('Cache-Control: private, must-revalidate');
    header('Pragma: private');
    header('Expires: 0');
    
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    readfile($file_path);
    exit();
}

// For view action, handle different file types
switch ($file_extension) {
    case 'pdf':
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $download_filename . '"');
        break;
    case 'jpg':
    case 'jpeg':
        header('Content-Type: image/jpeg');
        break;
    case 'png':
        header('Content-Type: image/png');
        break;
    case 'gif':
        header('Content-Type: image/gif');
        break;
    default:
        // For other file types, force download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $download_filename . '"');
}

header('Content-Length: ' . $file_size);
header('Cache-Control: private, must-revalidate');
header('Pragma: private');
header('Expires: 0');

if (ob_get_level()) {
    ob_end_clean();
}

readfile($file_path);
exit();
?>