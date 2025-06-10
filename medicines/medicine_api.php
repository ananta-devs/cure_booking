<?php
// Prevent any output before JSON headers
ob_start();

// Start session for authentication
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "cure_booking";

// Set error reporting to prevent HTML errors from being displayed
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);     // Log errors instead

// Headers - Set these early and ensure no output before them
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Clean any output buffer and exit
    ob_end_clean();
    exit(0);
}

// Function to send JSON response and exit
function sendJsonResponse($data, $httpCode = 200) {
    // Clean any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to handle special characters properly
    $conn->set_charset("utf8");

    // Determine action based on request method and parameters
    $action = isset($_GET['action']) ? $_GET['action'] : 'get_medicines';
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = 'save_order';
    }

    switch ($action) {
        case 'get_medicines':
            getMedicines($conn);
            break;
            
        case 'save_order':
            // Check authentication for order placement
            if (!isUserLoggedIn()) {
                sendJsonResponse([
                    'error' => true,
                    'message' => 'Authentication required. Please log in to place an order.',
                    'redirect' => 'login.php'
                ], 401);
            }
            saveMedicineOrder($conn);
            break;
            
        case 'get_orders':
            // Check authentication for viewing orders
            if (!isUserLoggedIn()) {
                sendJsonResponse([
                    'error' => true,
                    'message' => 'Authentication required. Please log in to view orders.',
                    'redirect' => 'login.php'
                ], 401);
            }
            getUserOrders($conn);
            break;
            
        case 'get_user_info':
            // New endpoint to get logged-in user info for auto-filling
            if (!isUserLoggedIn()) {
                sendJsonResponse([
                    'error' => true,
                    'message' => 'Authentication required.'
                ], 401);
            }
            getUserInfo($conn);
            break;
            
        default:
            sendJsonResponse([
                'error' => true,
                'message' => 'Invalid action specified'
            ], 400);
            break;
    }

} catch (Exception $e) {
    sendJsonResponse([
        'error' => true,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * Check if user is logged in
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);
}

/**
 * Get logged-in user information
 */
function getUserInfo($conn) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception("User not authenticated");
        }
        
        // Assuming you have a users table - adjust table/column names as needed
        $sql = "SELECT id, name, email, phone FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            sendJsonResponse([
                'error' => false,
                'user' => [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'] ?? ''
                ]
            ]);
        } else {
            throw new Exception("User not found");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Error fetching user info: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Get medicines from database with optional search
 */
function getMedicines($conn) {
    try {
        $query = isset($_GET['query']) ? trim($_GET['query']) : '';
        
        if (empty($query)) {
            // Return all medicines if no search query
            $sql = "SELECT id, name, manufacturer_name, pack_size, price, composition1, composition2 
                    FROM medicines 
                    ORDER BY name ASC 
                    LIMIT 1000";
            $stmt = $conn->prepare($sql);
        } else {
            // Search medicines by name, manufacturer, or composition
            $searchTerm = "%" . $query . "%";
            $sql = "SELECT id, name, manufacturer_name, pack_size, price, composition1, composition2 
                    FROM medicines 
                    WHERE name LIKE ? 
                       OR manufacturer_name LIKE ? 
                       OR composition1 LIKE ? 
                       OR composition2 LIKE ?
                    ORDER BY 
                        CASE 
                            WHEN name LIKE ? THEN 1
                            WHEN manufacturer_name LIKE ? THEN 2
                            WHEN composition1 LIKE ? THEN 3
                            ELSE 4
                        END,
                        name ASC 
                    LIMIT 1000";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $medicines = [];
        while ($row = $result->fetch_assoc()) {
            // Clean and format the data
            $medicine = [
                'id' => (int)$row['id'],
                'name' => trim($row['name']),
                'manufacturer_name' => trim($row['manufacturer_name']),
                'pack_size' => trim($row['pack_size']),
                'price' => number_format((float)$row['price'], 2, '.', ''),
                'composition1' => trim($row['composition1']),
                'composition2' => trim($row['composition2'])
            ];
            $medicines[] = $medicine;
        }
        
        sendJsonResponse([
            'error' => false,
            'medicines' => $medicines,
            'total_count' => count($medicines),
            'search_query' => $query
        ]);
        
        $stmt->close();
        
    } catch (Exception $e) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Error fetching medicines: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Save medicine order to database with user booking information and cart items
 */
function saveMedicineOrder($conn) {
    try {
        // Get POST data
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $cartData = isset($_POST['cart']) ? $_POST['cart'] : '';
        $totalAmount = isset($_POST['totalAmount']) ? (float)$_POST['totalAmount'] : 0;
        
        // Get user information from session
        $bookedByUserId = $_SESSION['user_id'] ?? 0;
        $bookedByEmail = $_SESSION['user_email'] ?? '';
        $bookedByName = $_SESSION['user_name'] ?? '';
        
        // Validate required fields
        if (empty($name) || empty($phone) || empty($email) || empty($address) || empty($cartData)) {
            throw new Exception("All fields are required");
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Validate phone number (basic validation)
        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            throw new Exception("Phone number must be 10-15 digits");
        }
        
        // Decode cart data
        $cart = json_decode($cartData, true);
        if (!$cart || !is_array($cart)) {
            throw new Exception("Invalid cart data");
        }
        
        // Generate unique order number
        $orderNumber = 'MED-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        // Start transaction
        $conn->begin_transaction();
        
        // Insert order into medicine_orders table with booking information
        $orderSql = "INSERT INTO medicine_orders 
                    (booked_by_user_id, booked_by_email, booked_by_name, order_number, name, phone, email, address, total_amount, order_date, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', NOW())";
        
        $orderStmt = $conn->prepare($orderSql);
        
        if (!$orderStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $orderStmt->bind_param("isssssssd", 
            $bookedByUserId, 
            $bookedByEmail, 
            $bookedByName, 
            $orderNumber, 
            $name, 
            $phone, 
            $email, 
            $address, 
            $totalAmount
        );
        
        if (!$orderStmt->execute()) {
            throw new Exception("Error inserting order: " . $orderStmt->error);
        }
        
        $orderId = $conn->insert_id;
        $orderStmt->close();
        
        // Insert cart items into medicine_order_items table
        $itemSql = "INSERT INTO medicine_order_items (order_id, medicine_name, medicine_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?)";
        $itemStmt = $conn->prepare($itemSql);
        
        if (!$itemStmt) {
            throw new Exception("Prepare failed for items: " . $conn->error);
        }
        
        foreach ($cart as $item) {
            $medicineName = $item['name'] ?? '';
            $medicinePrice = (float)($item['price'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            $subtotal = $medicinePrice * $quantity;
            
            if (empty($medicineName) || $medicinePrice <= 0 || $quantity <= 0) {
                throw new Exception("Invalid cart item data");
            }
            
            $itemStmt->bind_param("isdid", $orderId, $medicineName, $medicinePrice, $quantity, $subtotal);
            
            if (!$itemStmt->execute()) {
                throw new Exception("Error inserting order item: " . $itemStmt->error);
            }
        }
        
        $itemStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        sendJsonResponse([
            'status' => 'success',
            'message' => 'Order placed successfully',
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn && $conn->ping()) {
            $conn->rollback();
        }
        
        sendJsonResponse([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

/**
 * Get user orders with booking information and items
 */
function getUserOrders($conn) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception("User not authenticated");
        }
        
        // Get orders for the logged-in user with items
        $sql = "SELECT 
                    mo.id, 
                    mo.order_number, 
                    mo.booked_by_user_id, 
                    mo.booked_by_email, 
                    mo.booked_by_name,
                    mo.name, 
                    mo.phone, 
                    mo.email, 
                    mo.address, 
                    mo.total_amount, 
                    mo.order_date, 
                    mo.status, 
                    mo.created_at, 
                    mo.updated_at,
                    GROUP_CONCAT(
                        CONCAT(moi.medicine_name, ' (₹', moi.medicine_price, ' × ', moi.quantity, ')')
                        SEPARATOR ', '
                    ) as order_items
                FROM medicine_orders mo
                LEFT JOIN medicine_order_items moi ON mo.id = moi.order_id
                WHERE mo.booked_by_user_id = ? 
                GROUP BY mo.id
                ORDER BY mo.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => $row['id'],
                'order_number' => $row['order_number'],
                'booked_by_user_id' => $row['booked_by_user_id'],
                'booked_by_email' => $row['booked_by_email'],
                'booked_by_name' => $row['booked_by_name'],
                'customer_name' => $row['name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'address' => $row['address'],
                'total_amount' => $row['total_amount'],
                'order_date' => $row['order_date'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'order_items' => $row['order_items']
            ];
        }
        
        sendJsonResponse([
            'error' => false,
            'orders' => $orders,
            'total_orders' => count($orders)
        ]);
        
        $stmt->close();
        
    } catch (Exception $e) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Error fetching orders: ' . $e->getMessage()
        ], 500);
    }
}
?>