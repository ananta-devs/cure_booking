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

// Check if report is available (status should be 'Upload Done' or 'Completed')
if (!in_array($order['status'], ['Upload Done'])) {
    die("Lab report is not yet available. Current status: " . $order['status']);
}

// Check if report file exists
if (empty($order['report_file'])) {
    die("No report file found for this order");
}

// Define the upload directory where reports are stored
// Adjust this path according to your server setup
$upload_dir = "http://localhost/adminhub/lab-bookings/uploads/reports/";  // Change this to your actual upload directory
$file_path = $upload_dir . $order['report_file'];

// Check if file exists on server
if (!file_exists($file_path)) {
    die("Report file not found on server. Please contact support.");
}

// Get file extension to determine content type
$file_extension = strtolower(pathinfo($order['report_file'], PATHINFO_EXTENSION));
$content_type = 'application/octet-stream'; // Default

switch ($file_extension) {
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'jpg':
    case 'jpeg':
        $content_type = 'image/jpeg';
        break;
    case 'png':
        $content_type = 'image/png';
        break;
    case 'gif':
        $content_type = 'image/gif';
        break;
    default:
        $content_type = 'application/octet-stream';
}

// Generate a user-friendly filename
$download_filename = "Lab_Report_" . $order['booking_id'] . "_" . $order['customer_name'] . "." . $file_extension;
$download_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $download_filename); // Sanitize filename

// Set headers for file download
header('Content-Type: ' . $content_type);
header('Content-Length: ' . filesize($file_path));
header('Content-Disposition: attachment; filename="' . $download_filename . '"');
header('Cache-Control: private, must-revalidate');
header('Pragma: private');
header('Expires: 0');

// Clear any output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Output the file
readfile($file_path);
exit();
?>