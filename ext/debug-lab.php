<?php
// Debug script to test lab booking data retrieval
// Save this as debug-lab.php and run it to identify the issue

session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== LAB BOOKINGS DEBUG SCRIPT ===\n\n";

// 1. Check session
echo "1. SESSION CHECK:\n";
if (!isset($_SESSION['clinic_id'])) {
    echo "❌ ERROR: No clinic_id in session\n";
    echo "Session data: " . print_r($_SESSION, true) . "\n";
    exit("Please login first\n");
} else {
    echo "✅ Session clinic_id: " . $_SESSION['clinic_id'] . "\n";
    echo "✅ Session clinic_name: " . ($_SESSION['clinic_name'] ?? 'Not set') . "\n";
}

// 2. Database connection test
echo "\n2. DATABASE CONNECTION:\n";
try {
    include_once './include/database_connection.php';
    if ($conn->connect_error) {
        echo "❌ Connection failed: " . $conn->connect_error . "\n";
        exit();
    } else {
        echo "✅ Database connected successfully\n";
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
    exit();
}

$clinic_id = (int)$_SESSION['clinic_id'];

// 3. Check if tables exist
echo "\n3. TABLE STRUCTURE CHECK:\n";
$tables_to_check = ['lab_orders', 'lab_order_items'];

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists\n";
        
        // Check table structure
        $structure = $conn->query("DESCRIBE $table");
        echo "   Columns: ";
        $columns = [];
        while ($row = $structure->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        echo implode(', ', $columns) . "\n";
    } else {
        echo "❌ Table '$table' does not exist\n";
    }
}

// 4. Check data in tables
echo "\n4. DATA CHECK:\n";

// Check lab_orders table
$count_query = "SELECT COUNT(*) as total FROM lab_orders WHERE clinic_id = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $clinic_id);
$stmt->execute();
$count_result = $stmt->get_result();
$count_row = $count_result->fetch_assoc();

echo "Lab Orders for clinic_id $clinic_id: " . $count_row['total'] . " records\n";

if ($count_row['total'] > 0) {
    // Show sample data
    echo "\n5. SAMPLE DATA:\n";
    $sample_query = "SELECT id, booking_id, customer_name, status, created_at FROM lab_orders WHERE clinic_id = ? LIMIT 3";
    $stmt = $conn->prepare($sample_query);
    $stmt->bind_param("i", $clinic_id);
    $stmt->execute();
    $sample_result = $stmt->get_result();
    
    while ($row = $sample_result->fetch_assoc()) {
        echo "ID: {$row['id']}, Booking ID: {$row['booking_id']}, Customer: {$row['customer_name']}, Status: {$row['status']}, Created: {$row['created_at']}\n";
    }
} else {
    echo "No lab orders found for this clinic\n";
}

// 5. Test the actual query from your API
echo "\n6. TESTING ACTUAL API QUERY:\n";
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

try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "❌ Query preparation failed: " . $conn->error . "\n";
    } else {
        $stmt->bind_param("i", $clinic_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "Query executed successfully\n";
        echo "Number of rows returned: " . $result->num_rows . "\n";
        
        if ($result->num_rows > 0) {
            echo "\nFirst record:\n";
            $first_row = $result->fetch_assoc();
            print_r($first_row);
        }
    }
} catch (Exception $e) {
    echo "❌ Query execution error: " . $e->getMessage() . "\n";
}

// 6. Check for any PHP errors
echo "\n7. ERROR LOG CHECK:\n";
$error_log = error_get_last();
if ($error_log) {
    echo "Last PHP error: " . print_r($error_log, true) . "\n";
} else {
    echo "No recent PHP errors\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
$conn->close();
?>