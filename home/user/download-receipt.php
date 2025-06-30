<?php
// download-receipt.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID and type are provided
if (!isset($_GET['order_id']) || !isset($_GET['type'])) {
    die("Invalid request");
}

$order_id = (int)$_GET['order_id'];
$order_type = $_GET['type'];

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

$user_email = $_SESSION['user_email'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

if ($order_type === 'lab') {
    // Get lab order details
    $stmt = $pdo->prepare("
        SELECT 
            lo.*,
            GROUP_CONCAT(
                CONCAT(loi.test_name, ' - ₹', loi.test_price)
                SEPARATOR '\n'
            ) as items
        FROM lab_orders lo
        LEFT JOIN lab_order_items loi ON lo.id = loi.order_id
        WHERE lo.id = ? AND (lo.email = ? OR lo.booked_by_email = ?)
        GROUP BY lo.id
    ");
    $stmt->execute([$order_id, $user_email, $user_email]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Get medicine order details
    $stmt = $pdo->prepare("
        SELECT 
            mo.*,
            mo.name as customer_name,
            mo.order_date as created_at,
            GROUP_CONCAT(
                CONCAT(moi.medicine_name, ' (Qty: ', moi.quantity, ') - ₹', moi.medicine_price)
                SEPARATOR '\n'
            ) as items
        FROM medicine_orders mo
        LEFT JOIN medicine_order_items moi ON mo.id = moi.order_id
        WHERE mo.id = ? AND (mo.email = ? OR mo.booked_by_email = ?)
        GROUP BY mo.id
    ");
    $stmt->execute([$order_id, $user_email, $user_email]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$order) {
    die("Order not found or access denied");
}

// Generate receipt content
$receipt_number = $order_type === 'lab' ? 'LAB-' . $order['id'] : 'MED-' . ($order['order_number'] ?? $order['id']);
$receipt_date = date('d-M-Y_H-i-s');
$filename = "Receipt_{$receipt_number}_{$receipt_date}.txt";

// Create receipt content
$receipt_content = "
==============================================
              CUREBOOKING RECEIPT
==============================================

Receipt Number: {$receipt_number}
Order Type: " . strtoupper($order_type) . "
Date: " . date('d M Y, g:i A', strtotime($order['created_at'])) . "

==============================================
                PATIENT DETAILS
==============================================

Name: {$order['customer_name']}
Phone: {$order['phone']}
Email: {$order['email']}
Address: {$order['address']}
";

// Add booked by information if different from patient
if (!empty($order['booked_by_email']) && $order['booked_by_email'] !== $order['email']) {
    $receipt_content .= "
Booked By: {$order['booked_by_name']}
Booker Email: {$order['booked_by_email']}
";
}

$receipt_content .= "
==============================================
                ORDER DETAILS
==============================================
";

if ($order_type === 'lab') {
    $receipt_content .= "
Sample Collection Date: " . date('d M Y', strtotime($order['sample_collection_date'])) . "
Time Slot: {$order['time_slot']}
";
    if (!empty($order['booking_id'])) {
        $receipt_content .= "Booking ID: {$order['booking_id']}\n";
    }
} else {
    if (!empty($order['order_number'])) {
        $receipt_content .= "Order Number: {$order['order_number']}\n";
    }
}

$receipt_content .= "
Status: {$order['status']}

==============================================
                ITEMS ORDERED
==============================================

" . ($order['items'] ?? 'No items available') . "

==============================================
                PAYMENT SUMMARY
==============================================

Total Amount: ₹" . number_format($order['total_amount'], 2) . "

==============================================

Thank you for choosing CureBooking!
For support, contact us at support@curebooking.com

Generated on: " . date('d M Y, g:i A') . "
==============================================
";

// Set headers for file download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($receipt_content));

// Output the receipt content
echo $receipt_content;
exit();
?>