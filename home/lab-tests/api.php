<?php
session_start();

// Set timezone at the beginning of the script
date_default_timezone_set('Asia/Kolkata');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$conn = new mysqli("localhost", "root", "", "cure_booking");

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

$conn->set_charset("utf8");

// Set MySQL timezone to match PHP timezone
$conn->query("SET time_zone = '+05:30'"); // GMT+5:30 for India

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_tests':
            getLabTests($conn);
            break;
        case 'get_clinics':
            getClinics($conn);
            break;
        case 'save_booking':
            saveLabOrder($conn);
            break;
        case 'get_orders':
            getLabOrders($conn);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}

function getClinics($conn) {
    $stmt = $conn->prepare("SELECT clinic_id, clinic_name, location, contact_number, clinic_email 
                           FROM clinics WHERE status = 'active' ORDER BY clinic_name");
    
    if (!$stmt->execute()) {
        throw new Exception("Database query failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $clinics = [];
    
    while ($row = $result->fetch_assoc()) {
        $clinics[] = [
            'id' => (int)$row['clinic_id'],
            'name' => trim($row['clinic_name']),
            'location' => trim($row['location']),
            'phone' => trim($row['contact_number']),
            'email' => trim($row['clinic_email'])
        ];
    }

    echo json_encode(['status' => 'success', 'clinics' => $clinics]);
    $stmt->close();
}

function getLabTests($conn) {
    $search = trim($_GET['query'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(1000, (int)($_GET['limit'] ?? 1000)));
    $offset = ($page - 1) * $limit;

    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT id, name, sample_type, price, description,
                                CASE 
                                    WHEN name LIKE ? THEN 1
                                    WHEN name LIKE ? THEN 2
                                    WHEN sample_type LIKE ? THEN 3
                                    WHEN description LIKE ? THEN 4
                                    ELSE 5
                                END as search_rank
                                FROM lab_tests 
                                WHERE name LIKE ? OR sample_type LIKE ? OR description LIKE ?
                                ORDER BY search_rank, name
                                LIMIT ? OFFSET ?");
        
        $exactMatch = $search;
        $startsWith = $search . "%";
        $searchParam = "%" . $search . "%";
        
        $stmt->bind_param("sssssssii", 
            $exactMatch, $startsWith, $searchParam, $searchParam,
            $searchParam, $searchParam, $searchParam, 
            $limit, $offset);
            
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM lab_tests 
                                    WHERE name LIKE ? OR sample_type LIKE ? OR description LIKE ?");
        $countStmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    } else {
        $stmt = $conn->prepare("SELECT id, name, sample_type, price, description 
                                FROM lab_tests ORDER BY name LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM lab_tests");
    }

    if (!$stmt->execute()) {
        throw new Exception("Database query failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $labTests = [];
    
    while ($row = $result->fetch_assoc()) {
        $labTests[] = [
            'id' => (int)$row['id'],
            'name' => trim($row['name']),
            'sample' => trim($row['sample_type'] ?? 'N/A'),
            'price' => number_format((float)($row['price'] ?? 0), 2, '.', ''),
            'description' => trim($row['description'] ?? 'No description available')
        ];
    }

    $countStmt->execute();
    $totalCount = $countStmt->get_result()->fetch_assoc()['total'];

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
}

function saveLabOrder($conn) {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['username']) && !isset($_SESSION['logged_in'])) {
        throw new Exception('User not logged in. Please login to book tests.');
    }

    $bookedByEmail = $_SESSION['user_email'] ?? $_SESSION['email'] ?? $_SESSION['username'] ?? $_POST['email'] ?? 'unknown@example.com';
    $bookedByName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['full_name'] ?? $_SESSION['username'] ?? $_POST['name'] ?? 'Unknown User';

    $conn->autocommit(false);

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception('Invalid request method');
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $clinicId = (int)($_POST['clinic_id'] ?? 0);
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $cartData = $_POST['cart'] ?? '';
    $totalAmount = (float)($_POST['totalAmount'] ?? 0);

    // Validate required fields
    if (empty($name) || empty($phone) || empty($email) || empty($address) || empty($date) || empty($time)) {
        throw new Exception('All customer details are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (!preg_match('/^[0-9+\-\s()]{10,15}$/', $phone)) {
        throw new Exception('Invalid phone number format');
    }

    // Validate clinic and get clinic name
    $clinicName = null;
    if ($clinicId > 0) {
        $clinicStmt = $conn->prepare("SELECT clinic_name FROM clinics WHERE clinic_id = ? AND status = 'active'");
        $clinicStmt->bind_param("i", $clinicId);
        $clinicStmt->execute();
        $clinicResult = $clinicStmt->get_result();
        
        if ($clinicResult->num_rows === 0) {
            throw new Exception('Selected clinic is not valid or inactive');
        }
        
        $clinicName = $clinicResult->fetch_assoc()['clinic_name'];
        $clinicStmt->close();
    }

    $cart = is_string($cartData) ? json_decode($cartData, true) : $cartData;
    
    if (json_last_error() !== JSON_ERROR_NONE || empty($cart)) {
        throw new Exception('Invalid or empty cart data');
    }

    // Validate date
    $selectedDate = new DateTime($date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($selectedDate < $today) {
        throw new Exception('Cannot book for past dates');
    }

    // Validate cart items and calculate total
    $calculatedTotal = 0;
    $validatedCart = [];
    
    foreach ($cart as $index => $item) {
        if (!isset($item['id']) || !isset($item['name']) || !isset($item['price'])) {
            throw new Exception("Invalid item data at position " . ($index + 1));
        }

        $testId = (int)$item['id'];
        $testName = trim($item['name']);

        if (empty($testName) || $testId <= 0) {
            throw new Exception("Invalid item data: $testName (ID: $testId)");
        }

        $verifyStmt = $conn->prepare("SELECT name, price, sample_type FROM lab_tests WHERE id = ?");
        $verifyStmt->bind_param("i", $testId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        if ($verifyResult->num_rows === 0) {
            throw new Exception("Test with ID $testId not found");
        }
        
        $dbTest = $verifyResult->fetch_assoc();
        $verifyStmt->close();

        $testPrice = (float)$dbTest['price'];
        $calculatedTotal += $testPrice;

        $validatedCart[] = [
            'id' => $testId,
            'name' => $dbTest['name'],
            'price' => $testPrice,
            'sample_type' => $dbTest['sample_type'] ?? 'N/A'
        ];
    }

    if (abs($calculatedTotal - $totalAmount) > 0.01) {
        throw new Exception("Total amount mismatch. Calculated: ₹$calculatedTotal, Received: ₹$totalAmount");
    }

    $bookingId = 'LB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Use current timestamp with proper timezone
    $bookingTime = date('Y-m-d H:i:s');

    // Insert order
    $stmt = $conn->prepare("INSERT INTO lab_orders (
                booking_id, customer_name, phone, email, address, clinic_id, clinic_name,
                sample_collection_date, time_slot, total_amount, created_at, status,
                booked_by_email, booked_by_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?)");

    $stmt->bind_param("sssssisssdsss", 
        $bookingId, $name, $phone, $email, $address, 
        $clinicId, $clinicName, $date, $time, $calculatedTotal, $bookingTime, 
        $bookedByEmail, $bookedByName);

    if (!$stmt->execute()) {
        throw new Exception("Error creating lab order: " . $stmt->error);
    }

    $orderId = $conn->insert_id;
    $stmt->close();

    // Insert order items
    $itemStmt = $conn->prepare("INSERT INTO lab_order_items 
                               (order_id, test_name, test_price, sample_type) VALUES (?, ?, ?, ?)");

    foreach ($validatedCart as $item) {
        $itemStmt->bind_param("isds", $orderId, $item['name'], $item['price'], $item['sample_type']);
        if (!$itemStmt->execute()) {
            throw new Exception("Error saving order item: " . $itemStmt->error);
        }
    }

    $itemStmt->close();
    $conn->commit();
    $conn->autocommit(true);

    echo json_encode([
        'status' => 'success',
        'message' => 'Lab tests booking confirmed! Your booking ID is: ' . $bookingId,
        'bookingId' => $bookingId,
        'totalAmount' => number_format($calculatedTotal, 2, '.', ''),
        'itemsCount' => count($validatedCart),
        'bookedBy' => $bookedByName,
        'clinic' => $clinicName ?: 'Home Collection',
        'bookingTime' => $bookingTime // Return the actual booking time for debugging
    ]);
}

function getLabOrders($conn) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $filterByUser = isset($_GET['user_orders']) && $_GET['user_orders'] === 'true';
    $userEmail = $_SESSION['user_email'] ?? $_SESSION['email'] ?? $_SESSION['username'] ?? '';

    $baseQuery = "SELECT 
                    o.id, o.booking_id, o.customer_name, o.phone, o.email, 
                    o.address, o.clinic_id, o.clinic_name, o.sample_collection_date, o.time_slot, 
                    o.total_amount, o.created_at, o.status, o.booked_by_email, o.booked_by_name,
                    GROUP_CONCAT(
                        CONCAT(oi.test_name, ' (₹', oi.test_price, ')')
                        SEPARATOR '; '
                    ) as tests
                  FROM lab_orders o
                  LEFT JOIN lab_order_items oi ON o.id = oi.order_id";

    if ($filterByUser && $userEmail) {
        $stmt = $conn->prepare($baseQuery . " WHERE o.booked_by_email = ? 
                               GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("sii", $userEmail, $limit, $offset);
        
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM lab_orders WHERE booked_by_email = ?");
        $countStmt->bind_param("s", $userEmail);
    } else {
        $stmt = $conn->prepare($baseQuery . " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM lab_orders");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $row['total_amount'] = number_format((float)$row['total_amount'], 2, '.', '');
        $orders[] = $row;
    }
    
    $countStmt->execute();
    $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
    
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
}
?>