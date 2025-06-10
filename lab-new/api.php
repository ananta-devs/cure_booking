<?php
// Start session at the beginning
session_start();

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database connection
$conn = new mysqli("localhost", "root", "", "cure_booking");

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

// Set charset to handle special characters properly
$conn->set_charset("utf8");

// Get action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_tests':
            getLabTests($conn);
            break;

        case 'save_booking':
            saveLabOrder($conn);
            break;

        case 'get_orders':
            getLabOrders($conn);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action parameter']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}

function getLabTests($conn) {
    try {
        // Get search query if provided
        $search = isset($_GET['query']) ? trim($_GET['query']) : '';
        
        // Get pagination parameters - increase default limit for better search
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(1000, (int)$_GET['limit'])) : 1000; // Increased default
        $offset = ($page - 1) * $limit;

        // Prepare the SQL query with better search logic
        if (!empty($search)) {
            // Enhanced search with multiple conditions and better ranking
            $stmt = $conn->prepare("SELECT id, name, sample_type, price, description,
                                    CASE 
                                        WHEN name LIKE ? THEN 1
                                        WHEN name LIKE ? THEN 2
                                        WHEN sample_type LIKE ? THEN 3
                                        WHEN description LIKE ? THEN 4
                                        ELSE 5
                                    END as search_rank
                                    FROM lab_tests 
                                    WHERE name LIKE ? 
                                    OR sample_type LIKE ? 
                                    OR description LIKE ?
                                    ORDER BY search_rank ASC, name ASC
                                    LIMIT ? OFFSET ?");
            
            $exactMatch = $search;
            $startsWith = $search . "%";
            $searchParam = "%" . $search . "%";
            
            $stmt->bind_param("sssssssii", 
                $exactMatch, $startsWith, $searchParam, $searchParam,
                $searchParam, $searchParam, $searchParam, 
                $limit, $offset);
        } else {
            $stmt = $conn->prepare("SELECT id, name, sample_type, price, description 
                                    FROM lab_tests 
                                    ORDER BY name ASC
                                    LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
        }

        if (!$stmt->execute()) {
            throw new Exception("Database query failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        $labTests = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure all fields are properly handled
            $labTests[] = [
                'id' => (int)$row['id'],
                'name' => trim($row['name'] ?? ''),
                'sample' => trim($row['sample_type'] ?? 'N/A'),
                'price' => $row['price'] ? number_format((float)$row['price'], 2, '.', '') : '0.00',
                'description' => trim($row['description'] ?? 'No description available')
            ];
        }

        // Get total count for pagination info
        if (!empty($search)) {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM lab_tests 
                                        WHERE name LIKE ? 
                                        OR sample_type LIKE ? 
                                        OR description LIKE ?");
            $searchParam = "%" . $search . "%";
            $countStmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
        } else {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM lab_tests");
        }
        
        if (!$countStmt->execute()) {
            throw new Exception("Count query failed: " . $countStmt->error);
        }

        $countResult = $countStmt->get_result();
        $totalCount = $countResult->fetch_assoc()['total'];

        // Log for debugging
        error_log("Lab tests query - Search: '$search', Found: " . count($labTests) . ", Total: $totalCount");

        echo json_encode([
            'status' => 'success',
            'tests' => $labTests,
            'search_query' => $search,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalCount / $limit),
                'total_items' => (int)$totalCount,
                'items_per_page' => $limit,
                'items_returned' => count($labTests)
            ]
        ]);

        $stmt->close();
        $countStmt->close();
        
    } catch (Exception $e) {
        error_log("getLabTests error: " . $e->getMessage());
        throw new Exception("Error fetching lab tests: " . $e->getMessage());
    }
}

function saveLabOrder($conn) {
    try {
        // Check if user is logged in
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['username']) && !isset($_SESSION['logged_in'])) {
            throw new Exception('User not logged in. Please login to book tests.');
        }

        // Get user information from session
        $bookedByUserId = $_SESSION['user_id'] ?? 0;
        $bookedByEmail = $_SESSION['email'] ?? $_SESSION['username'] ?? '';
        $bookedByName = $_SESSION['name'] ?? $_SESSION['full_name'] ?? $_SESSION['username'] ?? '';

        // Start transaction
        $conn->autocommit(false);

        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            throw new Exception('Invalid request method');
        }

        // Collect form data
        $name = isset($_POST['name']) ? $conn->real_escape_string(trim($_POST['name'])) : '';
        $phone = isset($_POST['phone']) ? $conn->real_escape_string(trim($_POST['phone'])) : '';
        $email = isset($_POST['email']) ? $conn->real_escape_string(trim($_POST['email'])) : '';
        $address = isset($_POST['address']) ? $conn->real_escape_string(trim($_POST['address'])) : '';
        $date = isset($_POST['date']) ? $conn->real_escape_string(trim($_POST['date'])) : '';
        $time = isset($_POST['time']) ? $conn->real_escape_string(trim($_POST['time'])) : '';
        $cartData = isset($_POST['cart']) ? $_POST['cart'] : '';
        $totalAmount = isset($_POST['totalAmount']) ? floatval($_POST['totalAmount']) : 0;

        // Debug: Log the received cart data
        error_log("Received cart data: " . $cartData);

        // Validate required fields
        if (empty($name) || empty($phone) || empty($email) || empty($address) || 
            empty($date) || empty($time)) {
            throw new Exception('All customer details are required');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Validate phone number (basic validation)
        if (!preg_match('/^[0-9+\-\s()]{10,15}$/', $phone)) {
            throw new Exception('Invalid phone number format');
        }

        // Parse cart data with better error handling
        $cart = [];
        if (!empty($cartData)) {
            // Handle both JSON string and already decoded array
            if (is_string($cartData)) {
                $cart = json_decode($cartData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid cart data format: ' . json_last_error_msg());
                }
            } elseif (is_array($cartData)) {
                $cart = $cartData;
            }
        }

        if (empty($cart)) {
            throw new Exception('Cart is empty');
        }

        // Debug: Log parsed cart
        error_log("Parsed cart: " . print_r($cart, true));

        // Validate date is not in the past
        $selectedDate = new DateTime($date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($selectedDate < $today) {
            throw new Exception('Cannot book for past dates');
        }

        // Validate and calculate total from cart items
        $calculatedTotal = 0;
        $validatedCart = [];
        
        foreach ($cart as $index => $item) {
            // Ensure we have all required fields
            if (!isset($item['id']) || !isset($item['name']) || !isset($item['price'])) {
                error_log("Invalid item at index $index: " . print_r($item, true));
                throw new Exception("Invalid item data at position " . ($index + 1));
            }

            $testId = intval($item['id']);
            $testName = trim($item['name']);
            $testPrice = floatval($item['price']);
            $sampleType = isset($item['sample']) ? trim($item['sample']) : 'N/A';
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;

            if (empty($testName) || $testPrice <= 0 || $quantity <= 0 || $testId <= 0) {
                throw new Exception("Invalid item data: $testName (ID: $testId, Price: $testPrice, Qty: $quantity)");
            }

            // Verify test exists in database
            $verifyStmt = $conn->prepare("SELECT name, price, sample_type FROM lab_tests WHERE id = ?");
            $verifyStmt->bind_param("i", $testId);
            $verifyStmt->execute();
            $verifyResult = $verifyStmt->get_result();
            
            if ($verifyResult->num_rows === 0) {
                $verifyStmt->close();
                throw new Exception("Test with ID $testId not found in database");
            }
            
            $dbTest = $verifyResult->fetch_assoc();
            $verifyStmt->close();

            // Use database values for security (prevent price manipulation)
            $subtotal = floatval($dbTest['price']) * $quantity;
            $calculatedTotal += $subtotal;

            $validatedCart[] = [
                'id' => $testId,
                'name' => $dbTest['name'],
                'price' => floatval($dbTest['price']),
                'sample_type' => $dbTest['sample_type'] ?? 'N/A',
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
        }

        // Verify total amount (allow small floating point differences)
        if (abs($calculatedTotal - $totalAmount) > 0.01) {
            error_log("Total mismatch - Calculated: $calculatedTotal, Received: $totalAmount");
            throw new Exception("Total amount mismatch. Calculated: ₹$calculatedTotal, Received: ₹$totalAmount");
        }

        // Generate booking/order number
        $bookingId = 'LB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $bookingTime = date('Y-m-d H:i:s');

        // Insert main order record with user information
        $stmt = $conn->prepare("INSERT INTO lab_orders (
                    booking_id, customer_name, phone, email, address, 
                    sample_collection_date, time_slot, total_amount, created_at, status,
                    booked_by_user_id, booked_by_email, booked_by_name
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?)");

        $stmt->bind_param("sssssssdsiss", $bookingId, $name, $phone, $email, $address, 
                         $date, $time, $calculatedTotal, $bookingTime, 
                         $bookedByUserId, $bookedByEmail, $bookedByName);

        if (!$stmt->execute()) {
            throw new Exception("Error creating lab order: " . $stmt->error);
        }

        $orderId = $conn->insert_id;
        $stmt->close();

        // Insert order items using validated cart data
        $itemStmt = $conn->prepare("INSERT INTO lab_order_items (
                        order_id, test_name, test_price, sample_type, quantity, subtotal
                    ) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($validatedCart as $item) {
            $itemStmt->bind_param("isdsid", 
                $orderId, 
                $item['name'], 
                $item['price'], 
                $item['sample_type'], 
                $item['quantity'], 
                $item['subtotal']
            );

            if (!$itemStmt->execute()) {
                throw new Exception("Error saving order item: " . $itemStmt->error);
            }
        }

        $itemStmt->close();

        // Commit transaction
        $conn->commit();
        $conn->autocommit(true);

        echo json_encode([
            'status' => 'success',
            'message' => 'Lab tests booking confirmed! Your booking ID is: ' . $bookingId,
            'bookingId' => $bookingId,
            'totalAmount' => number_format($calculatedTotal, 2, '.', ''),
            'itemsCount' => count($validatedCart),
            'bookedBy' => $bookedByName
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $conn->autocommit(true);
        throw new Exception("Booking processing failed: " . $e->getMessage());
    }
}

function getLabOrders($conn) {
    try {
        // Get pagination parameters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;
        
        // Check if we should filter by logged-in user
        $filterByUser = isset($_GET['user_orders']) && $_GET['user_orders'] === 'true';
        $userId = $_SESSION['user_id'] ?? 0;
        
        // Get orders with items
        if ($filterByUser && $userId > 0) {
            $stmt = $conn->prepare("SELECT 
                                    o.id, o.booking_id, o.customer_name, o.phone, o.email, 
                                    o.address, o.sample_collection_date, o.time_slot, 
                                    o.total_amount, o.created_at, o.status,
                                    o.booked_by_user_id, o.booked_by_email, o.booked_by_name,
                                    GROUP_CONCAT(
                                        CONCAT(oi.test_name, ' (Qty: ', oi.quantity, ', Price: ₹', oi.test_price, ')')
                                        SEPARATOR '; '
                                    ) as tests
                                FROM lab_orders o
                                LEFT JOIN lab_order_items oi ON o.id = oi.order_id
                                WHERE o.booked_by_user_id = ?
                                GROUP BY o.id
                                ORDER BY o.created_at DESC
                                LIMIT ? OFFSET ?");
            $stmt->bind_param("iii", $userId, $limit, $offset);
        } else {
            $stmt = $conn->prepare("SELECT 
                                    o.id, o.booking_id, o.customer_name, o.phone, o.email, 
                                    o.address, o.sample_collection_date, o.time_slot, 
                                    o.total_amount, o.created_at, o.status,
                                    o.booked_by_user_id, o.booked_by_email, o.booked_by_name,
                                    GROUP_CONCAT(
                                        CONCAT(oi.test_name, ' (Qty: ', oi.quantity, ', Price: ₹', oi.test_price, ')')
                                        SEPARATOR '; '
                                    ) as tests
                                FROM lab_orders o
                                LEFT JOIN lab_order_items oi ON o.id = oi.order_id
                                GROUP BY o.id
                                ORDER BY o.created_at DESC
                                LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $row['total_amount'] = number_format((float)$row['total_amount'], 2, '.', '');
            $orders[] = $row;
        }
        
        // Get total count
        if ($filterByUser && $userId > 0) {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM lab_orders WHERE booked_by_user_id = ?");
            $countStmt->bind_param("i", $userId);
        } else {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM lab_orders");
        }
        
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalCount = $countResult->fetch_assoc()['total'];
        
        echo json_encode([
            'status' => 'success',
            'orders' => $orders,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalCount / $limit),
                'total_items' => (int)$totalCount,
                'items_per_page' => $limit
            ]
        ]);
        
        $stmt->close();
        $countStmt->close();
        
    } catch (Exception $e) {
        throw new Exception("Error fetching orders: " . $e->getMessage());
    }
}
?>