<?php
header('Content-Type: application/json');

// Include database connection
require_once '../include/database_connection.php';

// Start session for authentication check
session_start();

// Check if user is logged in
if (!isset($_SESSION['adm_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$orderId = intval($_GET['order_id']);

// Validate order ID
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Order ID']);
    exit();
}

try {
    // Get order details with better error handling
    $orderSql = "SELECT * FROM lab_orders WHERE id = ?";
    $orderStmt = $conn->prepare($orderSql);
    
    if (!$orderStmt) {
        throw new Exception('Failed to prepare order query: ' . $conn->error);
    }
    
    $orderStmt->bind_param("i", $orderId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    
    if ($orderResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    $order = $orderResult->fetch_assoc();
    
    // Get test items for this order with enhanced query
    $testsSql = "SELECT 
                    id,
                    test_name,
                    test_price,
                    sample_type,
                    subtotal,
                    created_at
                 FROM lab_order_items 
                 WHERE order_id = ? 
                 ORDER BY test_name, id";
    $testsStmt = $conn->prepare($testsSql);
    
    if (!$testsStmt) {
        throw new Exception('Failed to prepare tests query: ' . $conn->error);
    }
    
    $testsStmt->bind_param("i", $orderId);
    $testsStmt->execute();
    $testsResult = $testsStmt->get_result();
    
    $tests = [];
    $totalCalculated = 0;
    
    while ($test = $testsResult->fetch_assoc()) {
        // Ensure all fields have default values
        $test['test_name'] = $test['test_name'] ?? 'Unnamed Test';
        $test['test_price'] = floatval($test['test_price'] ?? 0);
        $test['sample_type'] = $test['sample_type'] ?? 'Not specified';
        $test['subtotal'] = floatval($test['subtotal'] ?? 0);
        
        // Calculate total for verification
        $totalCalculated += $test['subtotal'];
        
        $tests[] = $test;
    }
    
    // Add calculated totals for verification
    $order['calculated_total'] = $totalCalculated;
    $order['tests_count'] = count($tests);
    
    // Ensure all order fields have default values
    $order['customer_name'] = $order['customer_name'] ?? 'Unknown Customer';
    $order['phone'] = $order['phone'] ?? 'N/A';
    $order['email'] = $order['email'] ?? 'N/A';
    $order['address'] = $order['address'] ?? 'N/A';
    $order['booking_id'] = $order['booking_id'] ?? 'N/A';
    $order['status'] = $order['status'] ?? 'Unknown';
    $order['time_slot'] = $order['time_slot'] ?? 'N/A';
    $order['total_amount'] = floatval($order['total_amount'] ?? 0);
    
    // Format dates properly
    if (isset($order['sample_collection_date'])) {
        $order['sample_collection_date_formatted'] = date('M d, Y', strtotime($order['sample_collection_date']));
    }
    
    if (isset($order['created_at'])) {
        $order['created_at_formatted'] = date('M d, Y h:i A', strtotime($order['created_at']));
    }
    
    // Return the data with success status
    echo json_encode([
        'success' => true,
        'order' => $order,
        'tests' => $tests,
        'summary' => [
            'total_tests' => count($tests),
            'calculated_total' => $totalCalculated,
            'order_total' => floatval($order['total_amount'] ?? 0),
            'total_match' => abs($totalCalculated - floatval($order['total_amount'] ?? 0)) < 0.01
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_order_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error retrieving order details: ' . $e->getMessage()
    ]);
} finally {
    // Close prepared statements
    if (isset($orderStmt)) {
        $orderStmt->close();
    }
    if (isset($testsStmt)) {
        $testsStmt->close();
    }
}

// Close database connection
$conn->close();
?>