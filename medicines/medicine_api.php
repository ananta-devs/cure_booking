<?php
// Prevent any output before JSON
ob_start();
session_start();

// Suppress all PHP errors from displaying
error_reporting(0);
ini_set('display_errors', 0);

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

// Helper functions
function jsonResponse($data, $code = 200) {
    // Clear any previous output
    if (ob_get_level()) ob_clean();
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        jsonResponse(['error' => true, 'message' => 'Authentication required'], 401);
    }
}

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// Database connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    jsonResponse(['error' => true, 'message' => 'Database connection error'], 500);
}

// Route handler
$action = $_GET['action'] ?? ($_SERVER["REQUEST_METHOD"] == "POST" ? 'save_order' : 'get_medicines');

try {
    switch ($action) {
        case 'get_medicines':
            getMedicines($conn);
            break;
        case 'save_order':
            requireAuth();
            saveMedicineOrder($conn);
            break;
        case 'get_orders':
            requireAuth();
            getUserOrders($conn);
            break;
        case 'get_user_info':
            requireAuth();
            getUserInfo($conn);
            break;
        default:
            jsonResponse(['error' => true, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => true, 'message' => 'Server error occurred'], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

function getUserInfo($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Database query failed");
        }
        
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $stmt->close();
            jsonResponse(['error' => false, 'user' => $user]);
        } else {
            $stmt->close();
            jsonResponse(['error' => true, 'message' => 'User not found'], 404);
        }
    } catch (Exception $e) {
        jsonResponse(['error' => true, 'message' => 'Error fetching user info'], 500);
    }
}

function getMedicines($conn) {
    try {
        $query = trim($_GET['query'] ?? '');
        
        if (empty($query)) {
            $sql = "SELECT id, name, manufacturer_name, pack_size, price, composition1, composition2 FROM medicines ORDER BY name LIMIT 1000";
            $stmt = $conn->prepare($sql);
        } else {
            $search = "%" . $query . "%";
            $sql = "SELECT id, name, manufacturer_name, pack_size, price, composition1, composition2 
                FROM medicines 
                WHERE name LIKE ? OR manufacturer_name LIKE ? OR composition1 LIKE ? OR composition2 LIKE ?
                ORDER BY CASE WHEN name LIKE ? THEN 1 WHEN manufacturer_name LIKE ? THEN 2 ELSE 3 END, name 
                LIMIT 1000";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $search, $search, $search, $search, $search, $search);
        }
        
        if (!$stmt) {
            throw new Exception("Database query failed");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $medicines = [];
        while ($row = $result->fetch_assoc()) {
            $medicines[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'] ?? '',
                'manufacturer_name' => $row['manufacturer_name'] ?? '',
                'pack_size' => $row['pack_size'] ?? '',
                'price' => number_format((float)$row['price'], 2, '.', ''),
                'composition1' => $row['composition1'] ?? '',
                'composition2' => $row['composition2'] ?? ''
            ];
        }
        
        $stmt->close();
        jsonResponse([
            'error' => false,
            'medicines' => $medicines,
            'total_count' => count($medicines),
            'search_query' => $query
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => true, 'message' => 'Error fetching medicines'], 500);
    }
}

function saveMedicineOrder($conn) {
    try {
        // Input validation
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $cartData = $_POST['cart'] ?? '';
        $totalAmount = (float)($_POST['totalAmount'] ?? 0);
        
        if (empty($name) || empty($phone) || empty($email) || empty($address) || empty($cartData)) {
            jsonResponse(['status' => 'error', 'message' => 'All fields are required'], 400);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid email format'], 400);
        }
        
        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            jsonResponse(['status' => 'error', 'message' => 'Phone must be 10-15 digits'], 400);
        }
        
        $cart = json_decode($cartData, true);
        if (!$cart || !is_array($cart)) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid cart data'], 400);
        }
        
        $orderNumber = 'MED-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        $conn->begin_transaction();
        
        // Insert order
        $orderSql = "INSERT INTO medicine_orders (booked_by_user_id, booked_by_email, booked_by_name, order_number, name, phone, email, address, total_amount, order_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', NOW())";
        $orderStmt = $conn->prepare($orderSql);
        
        if (!$orderStmt) {
            throw new Exception("Database query failed");
        }
        
        $bookedByUserId = $_SESSION['user_id'] ?? 0;
        $bookedByEmail = $_SESSION['user_email'] ?? '';
        $bookedByName = $_SESSION['user_name'] ?? '';
        
        $orderStmt->bind_param("isssssssd", $bookedByUserId, $bookedByEmail, $bookedByName, $orderNumber, $name, $phone, $email, $address, $totalAmount);
        
        if (!$orderStmt->execute()) {
            throw new Exception("Failed to save order");
        }
        
        $orderId = $conn->insert_id;
        $orderStmt->close();
        
        // Insert order items
        $itemSql = "INSERT INTO medicine_order_items (order_id, medicine_name, medicine_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?)";
        $itemStmt = $conn->prepare($itemSql);
        
        if (!$itemStmt) {
            throw new Exception("Database query failed");
        }
        
        foreach ($cart as $item) {
            $medicineName = $item['name'] ?? '';
            $medicinePrice = (float)($item['price'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            $subtotal = $medicinePrice * $quantity;
            
            if (empty($medicineName) || $medicinePrice <= 0 || $quantity <= 0) {
                throw new Exception("Invalid cart item");
            }
            
            $itemStmt->bind_param("isdid", $orderId, $medicineName, $medicinePrice, $quantity, $subtotal);
            if (!$itemStmt->execute()) {
                throw new Exception("Failed to save order items");
            }
        }
        
        $itemStmt->close();
        $conn->commit();
        
        jsonResponse([
            'status' => 'success',
            'message' => 'Order placed successfully',
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ]);
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        jsonResponse(['status' => 'error', 'message' => 'Failed to process order'], 500);
    }
}

function getUserOrders($conn) {
    try {
        $sql = "SELECT mo.*, GROUP_CONCAT(CONCAT(moi.medicine_name, ' (₹', moi.medicine_price, ' × ', moi.quantity, ')') SEPARATOR ', ') as order_items
            FROM medicine_orders mo
            LEFT JOIN medicine_order_items moi ON mo.id = moi.order_id
            WHERE mo.booked_by_user_id = ? 
            GROUP BY mo.id
            ORDER BY mo.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database query failed");
        }
        
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => $row['id'],
                'order_number' => $row['order_number'],
                'customer_name' => $row['name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'address' => $row['address'],
                'total_amount' => $row['total_amount'],
                'order_date' => $row['order_date'],
                'status' => $row['status'],
                'order_items' => $row['order_items']
            ];
        }
        
        $stmt->close();
        jsonResponse([
            'error' => false,
            'orders' => $orders,
            'total_orders' => count($orders)
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => true, 'message' => 'Error fetching orders'], 500);
    }
}
?>