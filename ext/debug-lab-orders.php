<?php
// Debug script for lab orders not showing
// Add this temporarily to your lab-api.php or create as separate debug file

session_start();
header('Content-Type: application/json');

// Check session
echo "=== SESSION DEBUG ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Clinic ID in session: " . ($_SESSION['clinic_id'] ?? 'NOT SET') . "\n";
echo "Clinic Name: " . ($_SESSION['clinic_name'] ?? 'NOT SET') . "\n";
echo "\n";

if (!isset($_SESSION['clinic_id'])) {
    echo "ERROR: No clinic_id in session - user not logged in properly\n";
    exit();
}

// Database connection
include_once './include/database_connection.php';

if (!$conn) {
    echo "ERROR: Database connection failed\n";
    exit();
}

$clinic_id = (int)$_SESSION['clinic_id'];
echo "=== DATABASE DEBUG ===\n";
echo "Using clinic_id: $clinic_id\n";

// Check if lab_orders table exists
$check_table = "SHOW TABLES LIKE 'lab_orders'";
$result = $conn->query($check_table);
if ($result->num_rows == 0) {
    echo "ERROR: Table 'lab_orders' does not exist\n";
    exit();
} else {
    echo "✓ Table 'lab_orders' exists\n";
}

// Check table structure
echo "\n=== TABLE STRUCTURE ===\n";
$structure = "DESCRIBE lab_orders";
$result = $conn->query($structure);
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

// Count total records in lab_orders
$count_all = "SELECT COUNT(*) as total FROM lab_orders";
$result = $conn->query($count_all);
$total = $result->fetch_assoc()['total'];
echo "\nTotal records in lab_orders: $total\n";

// Count records for this clinic
$count_clinic = "SELECT COUNT(*) as total FROM lab_orders WHERE clinic_id = ?";
$stmt = $conn->prepare($count_clinic);
$stmt->bind_param("i", $clinic_id);
$stmt->execute();
$clinic_total = $stmt->get_result()->fetch_assoc()['total'];
echo "Records for clinic_id $clinic_id: $clinic_total\n";

// Show sample data for this clinic
echo "\n=== SAMPLE DATA ===\n";
$sample_sql = "SELECT id, booking_id, customer_name, clinic_id, status, created_at 
               FROM lab_orders 
               WHERE clinic_id = ? 
               ORDER BY created_at DESC 
               LIMIT 5";
$stmt = $conn->prepare($sample_sql);
$stmt->bind_param("i", $clinic_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Booking: {$row['booking_id']}, Customer: {$row['customer_name']}, Status: {$row['status']}\n";
    }
} else {
    echo "No records found for this clinic\n";
    
    // Show all clinic_ids in the table
    echo "\n=== ALL CLINIC IDs IN TABLE ===\n";
    $all_clinics = "SELECT DISTINCT clinic_id, COUNT(*) as count FROM lab_orders GROUP BY clinic_id";
    $result = $conn->query($all_clinics);
    while ($row = $result->fetch_assoc()) {
        echo "Clinic ID: {$row['clinic_id']}, Records: {$row['count']}\n";
    }
}

// Test the exact query from getBookings function
echo "\n=== TESTING MAIN QUERY ===\n";
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
            COUNT(loi.id) as test_count
        FROM lab_orders lo
        LEFT JOIN lab_order_items loi ON lo.id = loi.order_id
        WHERE lo.clinic_id = ?
        GROUP BY lo.id
        ORDER BY lo.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "ERROR: SQL prepare failed: " . $conn->error . "\n";
    exit();
}

$stmt->bind_param("i", $clinic_id);
if (!$stmt->execute()) {
    echo "ERROR: SQL execute failed: " . $stmt->error . "\n";
    exit();
}

$result = $stmt->get_result();
echo "Query executed successfully. Rows returned: " . $result->num_rows . "\n";

if ($result->num_rows > 0) {
    echo "Sample result:\n";
    $row = $result->fetch_assoc();
    print_r($row);
} else {
    echo "No results from main query\n";
    
    // Check if lab_order_items table exists
    $check_items = "SHOW TABLES LIKE 'lab_order_items'";
    $result = $conn->query($check_items);
    if ($result->num_rows == 0) {
        echo "WARNING: Table 'lab_order_items' does not exist - this might cause issues with JOIN\n";
    } else {
        echo "✓ Table 'lab_order_items' exists\n";
        
        // Check if there are items for any orders
        $count_items = "SELECT COUNT(*) as total FROM lab_order_items loi 
                       INNER JOIN lab_orders lo ON loi.order_id = lo.id 
                       WHERE lo.clinic_id = ?";
        $stmt = $conn->prepare($count_items);
        $stmt->bind_param("i", $clinic_id);
        $stmt->execute();
        $items_count = $stmt->get_result()->fetch_assoc()['total'];
        echo "Order items for this clinic: $items_count\n";
    }
}

$conn->close();
?>
